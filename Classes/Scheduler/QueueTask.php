<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Queue\QueueProcessorInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

abstract class QueueTask extends AbstractTask
{
    protected RegistryInterface $registry;

    protected QueueProcessorInterface $queueProcessor;

    protected function prepareTask(): void
    {
        $registryCollection = GeneralUtility::makeInstance(RegistryCollection::class);
        $this->registry = $registryCollection->getRegistryByClass(RegistryInterface::class);

        $queue = $this->registry->getPersistentQueue();
        $distributor = $this->registry->getDistributor();
        $this->queueProcessor = $this->registry->getQueueProcessor($queue, $distributor);
    }
}
