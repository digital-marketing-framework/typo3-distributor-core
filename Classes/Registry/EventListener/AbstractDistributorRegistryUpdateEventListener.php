<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Core\Initialization;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryUpdateEvent;

abstract class AbstractDistributorRegistryUpdateEventListener
{
    public function __construct(
        protected Initialization $initialization
    ) {
    }

    protected function initGlobalConfiguration(RegistryInterface $registry): void
    {
    }

    protected function initServices(RegistryInterface $registry): void
    {
    }

    protected function initPlugins(RegistryInterface $registry): void
    {
        $this->initialization->init(RegistryDomain::DISTRIBUTOR, $registry);
    }

    public function __invoke(DistributorRegistryUpdateEvent $event): void
    {
        $registry = $event->getRegistry();
        $type = $event->getUpdateType();
        switch ($type) {
            case RegistryUpdateType::GLOBAL_CONFIGURATION:
                $this->initGlobalConfiguration($registry);
                break;
            case RegistryUpdateType::SERVICE:
                $this->initServices($registry);
                break;
            case RegistryUpdateType::PLUGIN:
                $this->initPlugins($registry);
                break;
        }
    }
}
