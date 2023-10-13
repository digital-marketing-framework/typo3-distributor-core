<?php

use DigitalMarketingFramework\Core\Queue\QueueInterface;

defined('TYPO3') || exit;

$ll = 'LLL:EXT:dmf_distributor_core/Resources/Private/Language/locallang_db.xlf:';
$readOnly = false;

$GLOBALS['TCA']['tx_dmfdistributorcore_domain_model_queue_job'] = [
    'ctrl' => [
        'label' => 'created',
        'label_alt' => 'label',
        'label_alt_force' => 1,
        'tstamp' => 'changed',
        'crdate' => 'created',
        'title' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job',
        'origUid' => 't3_origuid',
        'searchFields' => 'label,hash,route_id,created,status,skipped,status_message,changed',
        'iconfile' => 'EXT:dmf_distributor_core/Resources/Public/Icons/QueueJob.svg',
        'default_sortby' => 'changed DESC',
    ],
    'interface' => [
        'showRecordFieldList' => 'label,hash,route_id,created,changed,status,skipped,status_message,serialized_data',
    ],
    'types' => [
        '0' => [
            'showitem' => 'label,hash,route_id,created,changed,status,skipped,status_message,serialized_data',
        ],
    ],
    'palettes' => [
        '0' => ['showitem' => 'label,hash,route_id,created,changed,status,skipped,status_message,serialized_data'],
    ],
    'columns' => [
        'label' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.label',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'skipped' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.skipped',
            'config' => [
                'type' => 'check',
                'readOnly' => $readOnly,
            ],
        ],
        'hash' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.hash',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'route_id' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.route_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'created' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.created',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => $readOnly,
            ],
        ],
        'changed' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.changed',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => $readOnly,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Queued', QueueInterface::STATUS_QUEUED],
                    ['Pending', QueueInterface::STATUS_PENDING],
                    ['Running', QueueInterface::STATUS_RUNNING],
                    ['Done', QueueInterface::STATUS_DONE],
                    ['Failed', QueueInterface::STATUS_FAILED],
                ],
                'readOnly' => $readOnly,
            ],
        ],
        'status_message' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.status_message',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'readOnly' => $readOnly,
            ],
        ],
        'serialized_data' => [
            'exclude' => 1,
            'label' => $ll . 'tx_dmfdistributorcore_domain_model_queue_job.serialized_data',
            'config' => [
                'type' => 'user',
                'renderType' => 'digitalMarketingFrameworkJsonFieldElement',
                'cols' => 40,
                'rows' => 15,
                'readOnly' => $readOnly,
            ],
        ],
    ],
];
