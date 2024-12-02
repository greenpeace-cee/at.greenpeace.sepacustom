<?php
use CRM_Sepacustom_ExtensionUtil as E;
return [
  'name' => 'SepaBankHoliday',
  'table' => 'civicrm_sepa_bank_holiday',
  'class' => 'CRM_Sepacustom_DAO_SepaBankHoliday',
  'getInfo' => fn() => [
    'title' => E::ts('SepaBankHoliday'),
    'title_plural' => E::ts('SepaBankHolidays'),
    'description' => E::ts('SEPA Bank Holidays'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique SepaBankHoliday ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'bank_holiday_date' => [
      'title' => E::ts('Bank Holiday Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => E::ts('Bank Holiday Date'),
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Bank Holiday Description'),
      'input_attrs' => [
        'rows' => 2,
        'cols' => 40,
        'label' => ts('Description'),
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('When was the bank holiday created.'),
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When was the bank holiday updated.'),
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
  ],
  'getIndices' => fn() => [
    'UI_bank_holiday_date' => [
      'fields' => [
        'bank_holiday_date' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getPaths' => fn() => [],
];
