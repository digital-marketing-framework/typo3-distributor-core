<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

/**
 * @deprecated
 */
class QueueCleanupTask extends QueueTask
{
    public function execute(): bool
    {
        $this->prepareTask();
        $this->queueProcessor->cleanupJobs();

        return true;
    }
}
