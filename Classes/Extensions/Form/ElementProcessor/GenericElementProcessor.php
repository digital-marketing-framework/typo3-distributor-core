<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Core\Model\Data\Value\MultiValue;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class GenericElementProcessor extends ElementProcessor
{
    protected function getElementClass(): string
    {
        return GenericFormElement::class;
    }

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        if ($element->getType() === 'Checkbox' && !(bool)$elementValue) {
            $elementValue = 0;
        }

        return is_array($elementValue) ? new MultiValue($elementValue) : $elementValue;
    }
}
