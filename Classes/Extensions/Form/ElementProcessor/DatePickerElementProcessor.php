<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DateTime;
use TYPO3\CMS\Form\Domain\Model\FormElements\DatePicker;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class DatePickerElementProcessor extends ElementProcessor
{
    protected function getElementClass(): string
    {
        return DatePicker::class;
    }

    protected function override(): bool
    {
        return true;
    }

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        $value = '';
        $properties = $element->getProperties();
        if ($elementValue instanceof DateTime) {
            if (isset($properties['dateFormat'])) {
                $dateFormat = $properties['dateFormat'];
                if (isset($properties['displayTimeSelector']) && $properties['displayTimeSelector'] === true) {
                    $dateFormat .= ' H:i';
                }
            } else {
                $dateFormat = DateTime::W3C;
            }
            $value = $elementValue->format($dateFormat);
        }
        return $value;
    }
}
