<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Digital Marketing Framework - Distributor',
    'description' => 'Send form data to different target systems',
    'category' => 'be',
    'author' => 'Michael Vöhringer',
    'author_email' => 'voehringer@mediatis.de',
    'author_company' => 'Mediatis AG',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'form' => '11.5.0-11.5.99',
            'form_fieldnames' => '>=3.5.0',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
