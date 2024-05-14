<?php

declare(strict_types=1);

use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Api\EndPoint;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;

return [
    Job::class => [
        'tableName' => 'tx_dmfdistributorcore_domain_model_queue_job',
    ],
    EndPoint::class => [
        'tableName' => 'tx_dmfdistributorcore_domain_model_api_endpoint',
    ],
];
