<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event;

use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;

class DistributorRegistryUpdateEvent
{
    public function __construct(
        protected RegistryInterface $registry,
        protected RegistryUpdateType $type,
    ) {
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    public function getUpdateType(): RegistryUpdateType
    {
        return $this->type;
    }
}
