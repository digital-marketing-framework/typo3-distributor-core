<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core;

use DigitalMarketingFramework\Core\Backend\Controller\SectionController\SectionControllerInterface;
use DigitalMarketingFramework\Core\GlobalConfiguration\Schema\GlobalConfigurationSchemaInterface;
use DigitalMarketingFramework\Core\Registry\ProxyArgument;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProviderInterface;
use DigitalMarketingFramework\Distributor\Core\DataSource\DistributorDataSourceStorageInterface;
use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface as DistributorRegistryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Backend\Controller\SectionController\DistributorEditSectionController;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsCampaignsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\ContentElementDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\LanguageCodeDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormDataSourceStorage;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormService;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3DistributorCoreInitialization extends DistributorCoreInitialization
{
    public function __construct(
        protected JobRepository $queue,
    ) {
        parent::__construct(
            'dmf_distributor_core',
            new DistributorCoreGlobalConfigurationSchema()
        );
    }

    protected function getAdditionalPluginArguments(string $interface, string $pluginClass, RegistryInterface $registry): array
    {
        if ($pluginClass === Typo3FormDataSourceStorage::class) {
            return [
                new ProxyArgument(static fn () => GeneralUtility::makeInstance(Typo3FormService::class)),
            ];
        }
        return parent::getAdditionalPluginArguments($interface, $pluginClass, $registry);
    }

    protected function getPluginDefinitions(): array
    {
        $pluginDefinitions = parent::getPluginDefinitions();
        $pluginDefinitions[RegistryDomain::CORE][SectionControllerInterface::class][] = DistributorEditSectionController::class;

        $pluginDefinitions[RegistryDomain::DISTRIBUTOR][DataProviderInterface::class][] = AdwordsCampaignsDataProvider::class;
        $pluginDefinitions[RegistryDomain::DISTRIBUTOR][DataProviderInterface::class][] = AdwordsDataProvider::class;
        $pluginDefinitions[RegistryDomain::DISTRIBUTOR][DataProviderInterface::class][] = ContentElementDataProvider::class;
        $pluginDefinitions[RegistryDomain::DISTRIBUTOR][DataProviderInterface::class][] = LanguageCodeDataProvider::class;

        $pluginDefinitions[RegistryDomain::DISTRIBUTOR][DistributorDataSourceStorageInterface::class][] = Typo3FormDataSourceStorage::class;

        return $pluginDefinitions;
    }

    public function initServices(string $domain, RegistryInterface $registry): void
    {
        parent::initServices($domain, $registry);

        if ($domain === RegistryDomain::DISTRIBUTOR && $registry instanceof DistributorRegistryInterface) {
            $this->queue->setGlobalConfiguration($registry->getGlobalConfiguration());
            $registry->setPersistentQueue($this->queue);
        }
    }
}
