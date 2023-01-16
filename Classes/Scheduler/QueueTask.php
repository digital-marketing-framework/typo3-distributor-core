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
    protected RegistryInterface $registry;

    protected QueueInterface&JobRepository $queue;

    protected function prepareTask()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->queue = $this->registry->getPersistentQueue();
    }
}
