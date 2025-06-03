<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema as OriginalDistributorCoreGlobalConfigurationSchema;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Schema\QueueSchema;

class DistributorCoreGlobalConfigurationSchema extends OriginalDistributorCoreGlobalConfigurationSchema
{
    public function __construct()
    {
        parent::__construct(new QueueSchema());
    }
}
