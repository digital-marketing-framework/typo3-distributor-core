<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Core\Registry\ProxyArgument;
use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsCampaignsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\ContentElementDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\LanguageCodeDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormDataSourceStorage;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormService;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class DistributorRegistryUpdateEventListener extends AbstractDistributorRegistryUpdateEventListener
{
    public function __construct(
        protected JobRepository $queue,
    ) {
        parent::__construct(
            new DistributorCoreInitialization(
                'dmf_distributor_core',
                new DistributorCoreGlobalConfigurationSchema()
            )
        );
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

        $registry->registerDistributorSourceStorage(
            Typo3FormDataSourceStorage::class,
            [
                new ProxyArgument(function() {
                    return GeneralUtility::makeInstance(Typo3FormService::class);
                }),
            ]
        );
    }
}
