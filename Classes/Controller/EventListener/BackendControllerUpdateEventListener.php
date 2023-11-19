<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller\EventListener;

use DigitalMarketingFramework\Typo3\Core\Controller\Event\BackendControllerUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Controller\DistributorListController;
use DigitalMarketingFramework\Typo3\Distributor\Core\Controller\DistributorStatisticsController;

class BackendControllerUpdateEventListener
{
    public function __invoke(BackendControllerUpdateEvent $event): void
    {
        $event->addControllerActions(DistributorStatisticsController::class, ['showStatistics', 'showErrors']);
        $event->addControllerActions(DistributorListController::class, ['list', 'listExpired', 'listStuck', 'listFailed', 'queue', 'run', 'delete']);
    }
}
