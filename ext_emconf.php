<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Anyrel - Distributor',
    'description' => 'Send form data to different target systems',
    'category' => 'be',
    'author_email' => 'info@mediatis.de',
    'author_company' => 'Mediatis AG',
    'state' => 'stable',
    'version' => '2.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'form' => '12.4.0-13.4.99',
            'form_fieldnames' => '3.5.0-4.99.99',
            'dmf_core' => '2.5.0-2.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
