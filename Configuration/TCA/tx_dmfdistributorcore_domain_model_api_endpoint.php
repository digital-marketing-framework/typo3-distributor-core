<?php

use DigitalMarketingFramework\Core\Queue\QueueInterface;

defined('TYPO3') || exit;

$ll = 'LLL:EXT:dmf_distributor_core/Resources/Private/Language/locallang_db.xlf:';

$GLOBALS['TCA']['tx_dmfdistributorcore_domain_model_api_endpoint'] = [
    'ctrl' => [
        'label' => 'name',
        'tstamp' => 'changed',
        'crdate' => 'created',
        'title' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint',
        'origUid' => 't3_origuid',
        'searchFields' => 'path_segment',
        'iconfile' => 'EXT:dmf_distributor_core/Resources/Public/Icons/ApiEndPoint.svg',
        'default_sortby' => 'changed DESC',
    ],
    'interface' => [
        'showRecordFieldList' => 'name,enabled,expose_to_frontend,disable_context,allow_context_override,configuration_document',
    ],
    'types' => [
        '0' => [
            'showitem' => 'name,enabled,expose_to_frontend,disable_context,allow_context_override,configuration_document',
        ],
    ],
    'palettes' => [
        '0' => ['showitem' => 'name,enabled,expose_to_frontend,disable_context,allow_context_override,configuration_document'],
    ],
    'columns' => [
        'name' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.name',
            'config' => [
                'type' => 'input',
            ],
        ],
        'enabled' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.enabled',
            'config' => [
                'type' => 'check',
            ],
        ],
        'disable_context' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.disable_context',
            'config' => [
                'type' => 'check',
            ],
        ],
        'allow_context_override' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.allow_context_override',
            'config' => [
                'type' => 'check',
            ],
        ],
        'expose_to_frontend' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_api_endpoint.expose_to_frontend',
            'config' => [
                'type' => 'check',
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
