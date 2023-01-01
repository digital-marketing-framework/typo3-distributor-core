<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Distributor\Core\DistributorPluginInitialization;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsCampaignsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\ContentElementDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\LanguageCodeDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryPluginUpdateEvent;

class DistributorRegistryPluginUpdateEventListener
{
    public function __invoke(DistributorRegistryPluginUpdateEvent $event): void
    {
        $registry = $event->getRegistry();
        DistributorPluginInitialization::initialize($registry);
        $registry->registerDataProvider(AdwordsCampaignsDataProvider::class);
        $registry->registerDataProvider(AdwordsDataProvider::class);
        $registry->registerDataProvider(ContentElementDataProvider::class);
        $registry->registerDataProvider(LanguageCodeDataProvider::class);
    }
}
