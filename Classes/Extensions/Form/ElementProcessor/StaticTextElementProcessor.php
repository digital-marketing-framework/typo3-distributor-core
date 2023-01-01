<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

class StaticTextElementProcessor extends IgnoredElementProcessor
{
    protected function getElementType(): string
    {
        return 'StaticText';
    }
}
