<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Anyrel - Distributor',
    'description' => 'Send form data to different target systems',
    'category' => 'be',
    'author_email' => 'info@mediatis.de',
    'author_company' => 'Mediatis AG',
    'state' => 'stable',
    'version' => '3.6.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'form' => '12.4.0-13.4.99',
            'form_fieldnames' => '3.5.0-4.99.99',
            'dmf_core' => '3.7.0-3.99.99',
            'dmf_template_engine_twig' => '3.0.0-3.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
