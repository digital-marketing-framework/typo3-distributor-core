<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Api\EventListener;

use DigitalMarketingFramework\Core\Api\RouteResolver\EntryRouteResolverInterface;
use DigitalMarketingFramework\Core\Utility\GeneralUtility;
use DigitalMarketingFramework\Distributor\Core\Api\EndPoint\EndPointStorageInterface;
use DigitalMarketingFramework\Distributor\Core\Api\RouteResolver\DistributorRouteResolverInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use DigitalMarketingFramework\Typo3\Core\Api\Event\FrontendJavaScriptSettingsUpdateEvent;

class FrontendJavaScriptSettingsUpdateEventListener
{
    protected EndPointStorageInterface $endPointStorage;

    protected EntryRouteResolverInterface $entryRouteResolver;

    protected DistributorRouteResolverInterface $distributorRouteResolver;

    public function __construct(
        protected Registry $registry
    ) {
        $this->endPointStorage = $registry->getEndPointStorage();
        $this->entryRouteResolver = $registry->getApiEntryRouteResolver();
        $this->distributorRouteResolver = $registry->getDistributorApiRouteResolver();
    }

    /**
     * @return array<array{id:string,url:string}>
     */
    protected function processDistributorEndpoints(): array
    {
        $result = [];
        $endPointRoute = $this->distributorRouteResolver->getEndPointRoute();
        $endPoints = $this->endPointStorage->getAllEndPoints();
        foreach ($endPoints as $endPoint) {
            $route = $endPointRoute->getResourceRoute(
                idAffix: $endPoint->getPathSegment(),
                variables: [
                    DistributorRouteResolverInterface::VARIABLE_END_POINT_SEGMENT => GeneralUtility::slugify($endPoint->getPathSegment()),
                ]
            );
            $result[] = [
                'id' => $route->getId(),
                'url' => '/' . $this->entryRouteResolver->getBasePath() . '/' . $route->getPath(),
            ];
        }
        return $result;
    }

    /**
     * @param array<array{id:string,url:string}> $plugins
     */
    protected function addPlugins(FrontendJavaScriptSettingsUpdateEvent $event, array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $event->addJavaScriptPlugin($plugin['id'], url: $plugin['url']);
        }
    }

    public function __invoke(FrontendJavaScriptSettingsUpdateEvent $event): void
    {
        $plugins = $this->processDistributorEndpoints();
        $this->addPlugins($event, $plugins);
    }
}
