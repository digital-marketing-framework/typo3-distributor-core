<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Core\SchemaDocument\Schema\IntegerSchema;
use DigitalMarketingFramework\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema as OriginalDistributorCoreGlobalConfigurationSchema;

class DistributorCoreGlobalConfigurationSchema extends OriginalDistributorCoreGlobalConfigurationSchema
{
    /**
     * @var string
     */
    public const KEY_QUEUE_PID = 'pid';

    /**
     * @var int
     */
    public const DEFAULT_QUEUE_PID = 0;

    public function __construct()
    {
        parent::__construct();

        $queuePidSchema = new IntegerSchema(static::DEFAULT_QUEUE_PID);
        $queuePidSchema->getRenderingDefinition()->setLabel('Storage PID');
        $this->queueSchema->addProperty(static::KEY_QUEUE_PID, $queuePidSchema);
    }
}
