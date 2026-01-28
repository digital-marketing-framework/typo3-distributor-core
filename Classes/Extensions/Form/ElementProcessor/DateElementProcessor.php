<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DateTime;
use DigitalMarketingFramework\Core\GlobalConfiguration\Settings\CoreSettings;
use DigitalMarketingFramework\Core\Model\Data\Value\DateTimeValue;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class DateElementProcessor extends ElementProcessor
{
    /**
     * @var string
     */
    public const DATE_FORMAT = 'Y-m-d';

    protected function getElementType(): string
    {
        return 'Date';
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
        $value = '';
        if ($elementValue instanceof DateTime) {
            // Extract date string and create DateTimeValue, preserving the date as entered
            $value = new DateTimeValue($elementValue->format('Y-m-d'), static::DATE_FORMAT, $this->getDefaultTimezone());
        }

        return $value;
    }
}
