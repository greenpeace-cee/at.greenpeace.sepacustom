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

require_once 'sepacustom.civix.php';

/**
 * This hook lets defer the collection date according to your banks preferences.
 * Most banks will only accept collection days that comply with their 'bank days'
 *
 * In this implementation, we only prevent the collection day to be on weekend,
 * but -depending on your bank- you might want to include national holidays as well.
 */
function sepacustom_civicrm_defer_collection_date(&$collection_date, $creditor_id) {
  // based on https://www.oenb.at/Service/Bankfeiertage.html
  $at_bank_holidays = [
    '2018-01-01',
    '2018-03-30',
    '2018-04-02',
    '2018-05-01',
    '2018-05-10',
    '2018-05-21',
    '2018-05-31',
    '2018-08-15',
    '2018-10-26',
    '2018-11-01',
    '2018-12-25',
    '2018-12-26',
    '2019-01-01',
    '2019-04-19',
    '2019-04-22',
    '2019-05-01',
    '2019-05-31',
    '2019-06-10',
    '2019-06-20',
    '2019-08-15',
    '2019-10-26',
    '2019-11-01',
    '2019-12-24',
    '2019-12-25',
    '2019-12-26',
    '2020-01-01',
    '2020-01-06',
    '2020-04-13',
    '2020-05-01',
    '2020-05-21',
    '2020-06-01',
    '2020-06-11',
    '2020-10-26',
    '2020-12-08',
    '2020-12-24',
    '2020-12-25',
    '2021-01-01',
    '2021-01-06',
    '2021-05-13',
    '2021-05-24',
    '2021-06-03',
    '2021-10-26',
    '2021-11-01',
    '2021-12-08',
    '2021-12-24',
    '2022-01-01',
    '2022-01-06',
    '2022-04-15',
    '2022-04-18',
    '2022-05-26',
    '2022-06-06',
    '2022-06-16',
    '2022-08-15',
    '2022-10-26',
    '2022-11-01',
    '2022-12-08',
    '2022-12-24',
    '2022-12-26',
    '2023-01-01',
    '2023-01-06',
    '2023-04-07',
    '2023-04-10',
    '2023-05-01',
    '2023-05-18',
    '2023-05-29',
    '2023-06-08',
    '2023-08-15',
    '2023-10-26',
    '2023-11-01',
    '2023-12-08',
    '2023-12-24',
    '2023-12-25',
    '2023-12-26',
    '2024-01-01',
    '2024-03-29',
    '2024-04-01',
    '2024-05-01',
    '2024-05-09',
    '2024-05-20',
    '2024-05-30',
    '2024-08-15',
    '2024-11-01',
    '2024-12-25',
    '2024-12-26',
  ];

  while ( date('N', strtotime($collection_date)) > 5            // if Saturday or Sunday
          || in_array($collection_date, $at_bank_holidays)) {   // ...or bank holiday
    // defer by one day
    $collection_date = date('Y-m-d', strtotime("+1 day", strtotime($collection_date)));
  }
}

/**
 * Generate custom SEPA mandate reference
 *
 * @see https://redmine.greenpeace.at/issues/460
 *
 * @author B. Endres (endres@systopia.de)
 */
function sepacustom_civicrm_create_mandate(&$mandate_parameters) {
  if (isset($mandate_parameters['reference']) && !empty($mandate_parameters['reference']))
    return;   // user defined mandate

  // GP-1-FRST-2016-Cxxxxxxx-xxx
  $prefix = Civi::settings()->get('sepacustom_reference_prefix') ?? 'GP';
  $reference_fmt = "{$prefix}-%s-%s-%s-C%07d-%03d";
  $creditor_id   = $mandate_parameters['creditor_id'];
  $type          = $mandate_parameters['type'];
  $year          = date('Y');
  $contact_id    = (int) $mandate_parameters['contact_id'];

  // find and set the first unused one
  $serial = 1;
  while ($serial < 1000) {
    $reference_candidate = sprintf($reference_fmt, $creditor_id, $type, $year, $contact_id, $serial);
    if (CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sdd_mandate WHERE reference = '$reference_candidate';")) {
      // this reference_candidate already exists;
      $serial += 1;
      continue;
    } else {
      // this reference_candidate is available
      $mandate_parameters['reference'] = $reference_candidate;
      return;
    }
  }

  error_log("at.greenpeace.sepacustom: Mandate reference generation failed. Please contact SYSTOPIA.");
  CRM_Core_Session::setStatus("Mandate reference generation failed. Please contact SYSTOPIA.", ts('Error'), 'error');
}

/**
 * This hook is called when a new transaction group is generated
 *
 * The default implementation is "TXG-${creditor_id}-${mode}-${collection_date}"
 *
 * Be aware the the reference has to be unique. You will have to use suffixes
 *  if your preferred reference is already in use.
 *
 * @param $reference        string  currently proposed reference (max. 35 characters!)
 * @param $collection_date  string  scheduled collection date
 * @param $mode             string  SEPA mode (OOFF, RCUR, FRST)
 * @param $creditor_id      string  SDD creditor ID
 *
 * @access public
 */
function sepacustom_civicrm_modify_txgroup_reference(&$reference, $creditor_id, $mode, $collection_date) {
  $prefix = Civi::settings()->get('sepacustom_reference_prefix') ?? 'GP';
  $base_reference = "{$prefix}-" . substr($mode, 0, 1) . "{$creditor_id}-{$collection_date}";
  $suffix = 0;

  while ($suffix < 1000) {
    // generate new reference
    if ($suffix) {
      $reference = $base_reference . "-{$suffix}";
    } else {
      $reference = $base_reference;
    }

    // check if it exists
    $reference_exists = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sdd_txgroup WHERE reference = %1",
      array(1 => array($reference, 'String')));
    if (!$reference_exists) {
      // we found it!
      return;
    } else {
      $suffix += 1;
    }
  }
}

/**
 * Connect newly generated SEPA contributions
 *  to the membership/contract
 *
 * @author B. Endres (endres@systopia.de)
 */
function sepacustom_civicrm_installment_created($mandate_id, $contribution_recur_id, $contribution_id) {
  try {
    CRM_Sepacustom_InstallmentProcessor::installmentCreated($mandate_id, $contribution_recur_id, $contribution_id);
  } catch (Exception $e) {
    // TODO: I don't think this will catch all the potential
    //   DB problems, especially if the table's aren't there.
    //   Maybe we need a verification, but *not* for every installment...
  }
}

/**
 * CiviCRM POST hook to adjust a mandate's OOFF contribution
 */
function sepacustom_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'SepaMandate') {
    CRM_Sepacustom_InstallmentProcessor::ooffCreated($objectId);
  }
}

/**
 * Generate TXN Message
 *
 * @see https://redmine.greenpeace.at/issues/434
 */
function sepacustom_civicrm_modify_txmessage(&$txmessage, $info, $creditor) {
  $txmessage .= " - {$info['reference']}";
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sepacustom_civicrm_config(&$config) {
  _sepacustom_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sepacustom_civicrm_install() {
  _sepacustom_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sepacustom_civicrm_enable() {
  _sepacustom_civix_civicrm_enable();
}
