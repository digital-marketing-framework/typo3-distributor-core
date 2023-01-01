<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Queue\QueueInterface;

class QueueCleanupTask extends QueueTask
{
    public const MIN_AGE = 3600 * 24 * 30; // 30 days

    protected int $minAge = self::MIN_AGE;
    protected bool $doneOnly = false;

    public function getMinAge(): int
    {
        return $this->minAge;
    }

    public function setMinAge(int $minAge): void
    {
        $this->minAge = $minAge;
    }

    public function getDoneOnly(): bool
    {
        return $this->doneOnly;
    }

    public function setDoneOnly(bool $doneOnly): void
    {
        $this->doneOnly = $doneOnly;
    }

    public function execute(): bool
    {
        $this->prepareTask();
        $this->queue->removeOldJobs(
            $this->minAge,
            $this->doneOnly ? [QueueInterface::STATUS_DONE] : []
        );
        return true;
    }
}
