<?php

return [
  'sepacustom_reference_prefix' => [
    'name'        => 'sepacustom_reference_prefix',
    'type'        => 'String',
    'default'     => 'GP',
    'html_type'   => 'text',
    'add'         => '4.7',
    'title'       => ts('SEPACustom Mandate Reference Prefix'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Prefix used when generating SEPA mandate references.'),
  ],
];
