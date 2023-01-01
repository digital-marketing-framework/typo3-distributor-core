<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

abstract class QueueTask extends AbstractTask
{
    protected int $pid = 0;

    protected RegistryInterface $registry;

    protected QueueInterface&JobRepository $queue;

    protected function prepareTask()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->queue = $this->registry->getPersistentQueue();
        $this->queue->setPid($this->pid);
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }
}
