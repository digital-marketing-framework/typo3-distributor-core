<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Typo3\Core\Registry\EventListener\AbstractCoreRegistryUpdateEventListener;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;

class CoreRegistryUpdateEventListener extends AbstractCoreRegistryUpdateEventListener
{
    public function __construct()
    {
        parent::__construct(
            new DistributorCoreInitialization(
                'dmf_distributor_core',
                new DistributorCoreGlobalConfigurationSchema()
            )
        );
    }
}
