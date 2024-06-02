<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Typo3\Core\Registry\EventListener\AbstractCoreRegistryUpdateEventListener;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;

class CoreRegistryUpdateEventListener extends AbstractCoreRegistryUpdateEventListener
{
    public function __construct()
    {
        $initialization = new DistributorCoreInitialization('dmf_distributor_core');
        $initialization->setGlobalConfigurationSchema(new DistributorCoreGlobalConfigurationSchema());
        parent::__construct($initialization);
    }
}
