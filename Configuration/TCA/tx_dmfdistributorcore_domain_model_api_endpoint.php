<?php

use DigitalMarketingFramework\Core\Queue\QueueInterface;

defined('TYPO3') || exit;

$ll = 'LLL:EXT:dmf_distributor_core/Resources/Private/Language/locallang_db.xlf:';

$GLOBALS['TCA']['tx_dmfdistributorcore_domain_model_api_endpoint'] = [
    'ctrl' => [
        'label' => 'path_segment',
        'tstamp' => 'changed',
        'crdate' => 'created',
        'title' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint',
        'origUid' => 't3_origuid',
        'searchFields' => 'path_segment',
        'iconfile' => 'EXT:dmf_distributor_core/Resources/Public/Icons/ApiEndPoint.svg',
        'default_sortby' => 'changed DESC',
    ],
    'interface' => [
        'showRecordFieldList' => 'path_segment,configuration_document',
    ],
    'types' => [
        '0' => [
            'showitem' => 'path_segment,configuration_document',
        ],
    ],
    'palettes' => [
        '0' => ['showitem' => 'path_segment,configuration_document'],
    ],
    'columns' => [
        'path_segment' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.path_segment',
            'config' => [
                'type' => 'input',
            ],
        ],
        'configuration_document' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.configuration_document',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
            ],
        ],
    ],
];
