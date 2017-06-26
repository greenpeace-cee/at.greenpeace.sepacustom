<?php
/*-------------------------------------------------------+
| Greenpeace AT CiviSEPA Customisations                  |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * Processor for civicrm__installment_created events
 */
class CRM_Sepacustom_InstallmentProcessor {

  /** cached lookups */
  private static $_custom_groups = array();
  private static $_custom_fields = array();
  private static $_creditor_account = array();

  /**
   * processes civicrm__installment_created event
   */
  public static function installmentCreated($mandate_id, $contribution_recur_id, $contribution_id) {
    // first: connect to membership
    try {
      CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO civicrm_membership_payment (membership_id,contribution_id)
           (SELECT
             civicrm_value_membership_payment.entity_id AS membership_id,
             %1 AS contribution_id
            FROM civicrm_value_membership_payment
            WHERE membership_recurring_contribution = %2)",
        array( 1 => array($contribution_id, 'Integer'),
               2 => array($contribution_recur_id, 'Integer')));
    } catch (Exception $e) {
      // TODO: I don't think this will catch all the potential
      //   DB problems, especially if the table's aren't there.
      //   Maybe we need a verification, but *not* for every installment...
    }


    // then: fill the account fields (from_ba, to_ba)
    $contribution_update = array();
    $to_ba       = self::getGPAccount($mandate_id);
    $to_ba_field = self::getCustomFieldKey('contribution_information', 'to_ba');
    if ($to_ba && $to_ba_field) {
      $contribution_update[$to_ba_field] = $to_ba;
    }

    $from_ba       = self::getDonorAccount($mandate_id);
    $from_ba_field = self::getCustomFieldKey('contribution_information', 'from_ba');
    if ($from_ba && $from_ba_field) {
      $contribution_update[$from_ba_field] = $from_ba;
    }

    if (!empty($contribution_update)) {
      $contribution_update['id'] = $contribution_id;
      civicrm_api3('Contribution', 'create', $contribution_update);
    }
  }


  /**
   * Get the GP bank account (cached),
   * i.e. the bank account connected to the mandate's creditor
   */
  public static function getGPAccount($mandate_id) {
    $mandate_id = (int) $mandate_id;

    if (!array_key_exists($mandate_id, self::$_creditor_account)) {
      self::$_creditor_account[$mandate_id] = CRM_Core_DAO::singleValueQuery("
        SELECT civicrm_bank_account.id
        FROM civicrm_sdd_mandate
        LEFT JOIN civicrm_sdd_creditor ON civicrm_sdd_creditor.id = civicrm_sdd_mandate.creditor_id
        LEFT JOIN civicrm_bank_account_reference ON civicrm_bank_account_reference.reference = civicrm_sdd_creditor.iban
        LEFT JOIN civicrm_bank_account ON civicrm_bank_account.id = civicrm_bank_account_reference.ba_id
        WHERE civicrm_sdd_mandate.id = $mandate_id
          AND civicrm_bank_account.contact_id = civicrm_sdd_creditor.creditor_id;");
    }
    return self::$_creditor_account[$mandate_id];
  }

  /**
   * Get the donor's bank account,
   * i.e. the bank account connected to the mandate
   */
  public static function getDonorAccount($mandate_id) {
    $mandate_id = (int) $mandate_id;
    return CRM_Core_DAO::singleValueQuery("
      SELECT civicrm_bank_account.id
      FROM civicrm_sdd_mandate
      LEFT JOIN civicrm_bank_account_reference ON civicrm_bank_account_reference.reference = civicrm_sdd_mandate.iban
      LEFT JOIN civicrm_bank_account ON civicrm_bank_account.id = civicrm_bank_account_reference.ba_id
      WHERE civicrm_sdd_mandate.id = $mandate_id
        AND civicrm_bank_account.contact_id = civicrm_sdd_mandate.contact_id;");
  }


  /**
   * get the key (e.g. "custom_32") of the given custom field
   */
  public static function getCustomFieldKey($custom_group_name, $custom_field_name) {
    $cache_key = "{$custom_group_name}--{$custom_field_name}";

    // load the custom field
    if (!array_key_exists($cache_key, self::$_custom_fields)) {
      // not known yet: get group first
      if (!array_key_exists($custom_group_name, self::$_custom_groups)) {
        // group is not known either!
        $custom_group = civicrm_api3('CustomGroup', 'getsingle', array(
            'name'   => $custom_group_name,
            'return' => 'id'));
        self::$_custom_groups[$custom_group_name] = $custom_group;
      }

      // now: load the field
      $custom_field = civicrm_api3('CustomField', 'getsingle', array(
        'custom_group_id' => self::$_custom_groups[$custom_group_name]['id'],
        'name'            => $custom_field_name,
        'return'          => 'id'));
      self::$_custom_fields[$cache_key] = $custom_field;
    }

    // process the custom field
    $custom_field = self::$_custom_fields[$cache_key];
    if ($custom_field && $custom_field['id']) {
      return 'custom_' . $custom_field['id'];
    } else {
      return NULL;
    }
  }
}