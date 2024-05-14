<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Api\EventListener;

use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use DigitalMarketingFramework\Typo3\Core\Api\Event\ApiRouteResolversUpdateEvent;

class ApiRouteResolversUpdateEventListener
{
    public function __construct(
        protected Registry $registry,
    ) {
    }

    public function __invoke(ApiRouteResolversUpdateEvent $event): void
    {
        $event->processRegistry($this->registry);
    }
}
