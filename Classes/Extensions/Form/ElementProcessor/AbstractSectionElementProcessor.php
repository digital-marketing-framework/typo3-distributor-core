<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;

class AbstractSectionElementProcessor extends IgnoredElementProcessor
{
    protected function getElementClass(): string
    {
        return AbstractSection::class;
    }
}
