<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

/**
 * @deprecated
 */
class QueueProcessorTask extends QueueTask
{
    public function execute(): bool
    {
        $this->prepareTask();
        $this->queueProcessor->updateJobsAndProcessBatch();

        return true;
    }
}
