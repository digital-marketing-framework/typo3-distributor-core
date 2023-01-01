<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

abstract class IgnoredElementProcessor extends ElementProcessor
{
    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        return null;
    }

    protected function override(): bool
    {
        return true;
    }
}
