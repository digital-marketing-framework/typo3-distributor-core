<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

class ContentElementProcessor extends IgnoredElementProcessor
{
    protected function getElementType(): string
    {
        return 'ContentElement';
    }
}
