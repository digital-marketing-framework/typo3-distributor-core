<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DateTime;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\GlobalConfiguration\Settings\CoreSettings;
use DigitalMarketingFramework\Core\Model\Data\Value\DateTimeValue;
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

    protected function getDefaultTimezone(): string
    {
        return $this->globalConfiguration->getGlobalSettings(CoreSettings::class)->getDefaultTimezone();
    }

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        if (!$element instanceof DatePicker) {
            throw new DigitalMarketingFrameworkException(sprintf('Field type DatePicker expected, found "%s".', $element::class), 5663861319);
        }

        $value = '';
        $properties = $element->getProperties();
        if ($elementValue instanceof DateTime) {
            $hasTimeSelector = isset($properties['displayTimeSelector']) && $properties['displayTimeSelector'] === true;

            if (isset($properties['dateFormat'])) {
                $dateFormat = $properties['dateFormat'];
                if ($hasTimeSelector) {
                    $dateFormat .= ' H:i';
                }
            } else {
                $dateFormat = DateTime::W3C;
            }

            // Extract date/time string and create DateTimeValue, preserving the values as entered
            $dateString = $hasTimeSelector ? $elementValue->format('Y-m-d H:i:s') : $elementValue->format('Y-m-d');

            $value = new DateTimeValue($dateString, $dateFormat, $this->getDefaultTimezone());
        }

        return $value;
    }
}
