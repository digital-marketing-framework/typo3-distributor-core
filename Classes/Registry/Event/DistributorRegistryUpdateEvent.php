<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event;

use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;

abstract class DistributorRegistryUpdateEvent
{
    public function __construct(
        protected RegistryInterface $registry,
    ) {
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }
}
