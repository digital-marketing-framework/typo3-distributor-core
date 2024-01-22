<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Digital Marketing Framework - Distributor',
    'description' => 'Send form data to different target systems',
    'category' => 'be',
    'author_email' => 'info@mediatis.de',
    'author_company' => 'Mediatis AG',
    'state' => 'stable',
    'version' => '1.2.2',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'form' => '11.5.0-12.4.99',
            'form_fieldnames' => '3.5.0-4.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
