<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Api\EventListener;

use DigitalMarketingFramework\Typo3\Core\Api\Event\FrontendJavaScriptUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;

class FrontendJavaScriptUpdateEventListener
{
    public function __construct(protected Registry $registry)
    {
    }

    public function __invoke(FrontendJavaScriptUpdateEvent $event): void
    {
        $event->processRegistry($this->registry);
    }
}
