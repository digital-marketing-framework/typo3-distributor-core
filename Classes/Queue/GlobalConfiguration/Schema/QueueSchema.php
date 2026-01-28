<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Core\Queue\GlobalConfiguration\Schema\QueueSchema as CoreQueueSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\IntegerSchema;

class QueueSchema extends CoreQueueSchema
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
        $this->addProperty(static::KEY_QUEUE_PID, $queuePidSchema);
    }
}
