<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Settings\QueueSettings;

/**
 * @deprecated
 */
class QueueCleanupTask extends QueueTask
{
    protected QueueSettings $queueSettings;

    protected function getQueueSettings(): QueueSettings
    {
        if (!isset($this->queueSettings)) {
            $this->queueSettings = $this->registry->getGlobalConfiguration()->getGlobalSettings(QueueSettings::class);
        }

        return $this->queueSettings;
    }

    public function execute(): bool
    {
        $this->prepareTask();
        $this->queueProcessor->cleanupJobs();

        return true;
    }
}
