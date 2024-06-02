<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry\EventListener;

use DigitalMarketingFramework\Core\Registry\RegistryCollectionInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;

class RegistryCollectionEventListener
{
    public function __construct(
        protected Registry $registry,
    ) {
    }

    public function __invoke(RegistryCollectionInterface $collection): void
    {
        $collection->addRegistry(RegistryDomain::DISTRIBUTOR, $this->registry);
    }
}
