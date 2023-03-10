<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\Factory\QueueDataFactory;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryServiceUpdateEvent;

class DistributorRegistryServiceUpdateEventListener
{
    public function __construct(
        protected JobRepository $queue,
    ) {}

    public function __invoke(DistributorRegistryServiceUpdateEvent $event): void
    {
        $event->getRegistry()->setPersistentQueue($this->queue);
    }
}
