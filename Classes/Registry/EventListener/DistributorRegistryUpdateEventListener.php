<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsCampaignsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\ContentElementDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\LanguageCodeDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;

class DistributorRegistryUpdateEventListener extends AbstractDistributorRegistryUpdateEventListener
{
    public function __construct(
        protected JobRepository $queue,
    ) {
        $initialization = new DistributorCoreInitialization('dmf_distributor_core');
        $initialization->setGlobalConfigurationSchema(new DistributorCoreGlobalConfigurationSchema());
        parent::__construct($initialization);
    }

    protected function initServices(RegistryInterface $registry): void
    {
        parent::initServices($registry);
        $registry->setPersistentQueue($this->queue);
    }

    protected function initPlugins(RegistryInterface $registry): void
    {
        parent::initPlugins($registry);
        $registry->registerDataProvider(AdwordsCampaignsDataProvider::class);
        $registry->registerDataProvider(AdwordsDataProvider::class);
        $registry->registerDataProvider(ContentElementDataProvider::class);
        $registry->registerDataProvider(LanguageCodeDataProvider::class);
    }
}
