<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DateTime;
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

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        $value = '';
        if ($elementValue instanceof DateTime) {
            $value = $elementValue->format(static::DATE_FORMAT);
        }

        return $value;
    }
}
