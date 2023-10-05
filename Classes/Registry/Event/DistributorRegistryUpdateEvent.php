<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event;

use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;

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
