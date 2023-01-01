<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

class HoneypotElementProcessor extends IgnoredElementProcessor
{
    protected function getElementType(): string
    {
        return 'Honeypot';
    }
}
