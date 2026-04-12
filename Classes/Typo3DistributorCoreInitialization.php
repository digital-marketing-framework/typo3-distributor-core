<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core;

use DigitalMarketingFramework\Core\Backend\UriRouteResolver\UriRouteResolverInterface;
use DigitalMarketingFramework\Core\Plugin\PluginInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProviderInterface;
use DigitalMarketingFramework\Distributor\Core\DataSource\DistributorDataSourceStorageInterface;
use DigitalMarketingFramework\Distributor\Core\DistributorCoreInitialization;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface as DistributorRegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\Typo3ProxyArgument;
use DigitalMarketingFramework\Typo3\Core\Typo3Initialization;
use DigitalMarketingFramework\Typo3\Distributor\Core\Backend\UriRouteResolver\DistributorEditUriRouteResolver;
use DigitalMarketingFramework\Typo3\Distributor\Core\Backend\UriRouteResolver\Typo3FormDataSourceEditUriRouteResolver;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsCampaignsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\AdwordsDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\ContentElementDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider\LanguageCodeDataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormDataSourceStorage;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormService;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\GlobalConfiguration\Schema\DistributorCoreGlobalConfigurationSchema;

class Typo3DistributorCoreInitialization extends Typo3Initialization
{
    /** @var array<"core"|"distributor"|"collector",array<class-string<PluginInterface>,array<string|int,class-string<PluginInterface>>>> */
    protected const PLUGINS = [
        RegistryDomain::CORE => [
            UriRouteResolverInterface::class => [
                DistributorEditUriRouteResolver::class,
                Typo3FormDataSourceEditUriRouteResolver::class,
            ],
        ],
        RegistryDomain::DISTRIBUTOR => [
            DataProviderInterface::class => [
                AdwordsCampaignsDataProvider::class,
                AdwordsDataProvider::class,
                ContentElementDataProvider::class,
                LanguageCodeDataProvider::class,
            ],
            DistributorDataSourceStorageInterface::class => [
                Typo3FormDataSourceStorage::class,
            ],
        ],
    ];

    public function __construct(
        protected JobRepository $queue,
    ) {
        parent::__construct(
            inner: new DistributorCoreInitialization('dmf_distributor_core'),
            packageName: 'typo3-distributor-core',
            packageAlias: 'dmf_distributor_core',
            globalConfigurationSchema: new DistributorCoreGlobalConfigurationSchema(),
        );
    }

    protected function getAdditionalPluginArguments(string $interface, string $pluginClass, RegistryInterface $registry): array
    {
        if ($pluginClass === Typo3FormDataSourceStorage::class) {
            return [new Typo3ProxyArgument(Typo3FormService::class)];
        }

        return parent::getAdditionalPluginArguments($interface, $pluginClass, $registry);
    }

    public function initServices(string $domain, RegistryInterface $registry): void
    {
        parent::initServices($domain, $registry);

        if ($registry instanceof DistributorRegistryInterface) {
            $this->queue->setGlobalConfiguration($registry->getGlobalConfiguration());
            $registry->setPersistentQueue($this->queue);
        }
    }
}
