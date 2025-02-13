<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Settings;

use DigitalMarketingFramework\Distributor\Core\Queue\GlobalConfiguration\Settings\QueueSettings as DistributorCoreQueueSettings;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Schema\QueueSchema;

class QueueSettings extends DistributorCoreQueueSettings
{
    public function getPid(): int
    {
        return $this->get(QueueSchema::KEY_QUEUE_PID);
    }
}
