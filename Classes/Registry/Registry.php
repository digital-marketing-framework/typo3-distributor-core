<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry;

use DigitalMarketingFramework\Distributor\Core\Model\Configuration\SubmissionConfigurationInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\Registry as CoreDistributorRegistry;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryGlobalConfigurationUpdateEvent;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryPluginUpdateEvent;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryServiceUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryPluginUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryServiceUpdateEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

class Registry extends CoreDistributorRegistry implements SingletonInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function initializeObject(): void
    {
        $this->eventDispatcher->dispatch(
            new CoreRegistryGlobalConfigurationUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryServiceUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryPluginUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new DistributorRegistryServiceUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new DistributorRegistryPluginUpdateEvent($this)
        );
    }
}
