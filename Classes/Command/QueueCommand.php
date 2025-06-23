<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Command;

use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Settings\QueueSettings;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueCommand extends Command
{
    protected RegistryInterface $registry;

    protected QueueInterface $queue;

    protected QueueSettings $queueSettings;

    protected function prepareTask(): void
    {
        $registryCollection = GeneralUtility::makeInstance(RegistryCollection::class);
        $this->registry = $registryCollection->getRegistryByClass(RegistryInterface::class);
        $this->queueSettings = $this->registry->getGlobalConfiguration()->getGlobalSettings(QueueSettings::class);
        $this->queue = $this->registry->getPersistentQueue();
    }
}
