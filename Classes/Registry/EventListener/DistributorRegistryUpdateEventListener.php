<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Typo3\Distributor\Core\Typo3DistributorCoreInitialization;

class DistributorRegistryUpdateEventListener extends AbstractDistributorRegistryUpdateEventListener
{
    public function __construct(
        Typo3DistributorCoreInitialization $initialization,
    ) {
        parent::__construct($initialization);
    }
}
