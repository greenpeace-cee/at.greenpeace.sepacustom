<?php

use CRM_Sepacustom_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_SEPA_Bank_Holidays',
    'entity' => 'SavedSearch',
    'cleanup' => 'never',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SEPA_Bank_Holidays',
        'label' => E::ts('SEPA Bank Holidays'),
        'api_entity' => 'SepaBankHoliday',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'bank_holiday_date',
            'description',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_SEPA_Bank_Holidays_SearchDisplay_SEPA_Bank_Holidays_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'never',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SEPA_Bank_Holidays_Table',
        'label' => E::ts('SEPA Bank Holidays Table'),
        'saved_search_id.name' => 'SEPA_Bank_Holidays',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'bank_holiday_date',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'bank_holiday_date',
              'dataType' => 'Date',
              'label' => E::ts('Bank Holiday Date'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'dataType' => 'Text',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'toolbar' => [
            [
              'path' => 'civicrm/sepa/holiday/manage',
              'icon' => 'fa-plus',
              'text' => E::ts('Add Bank Holiday'),
              'style' => 'primary',
              'condition' => [],
              'task' => '',
              'entity' => '',
              'action' => '',
              'join' => '',
              'target' => 'crm-popup',
            ],
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
