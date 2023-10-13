<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Form\Element;

use TYPO3\CMS\Backend\Form\Element\TextElement;

class JsonFieldElement extends TextElement
{
    /**
     * Render textarea and use whitespaces to format JSON
     *
     * @return array<mixed>
     */
    public function render(): array
    {
        $itemValue = (string)$this->data['parameterArray']['itemFormElValue'];
        if ($itemValue !== '') {
            // If value can be decoded into json, we encode it again with JSON_PRETTY_PRINT
            $itemValue = json_decode($itemValue, flags: JSON_THROW_ON_ERROR);
            if ((bool)$itemValue) {
                $this->data['parameterArray']['itemFormElValue'] = json_encode($itemValue, JSON_PRETTY_PRINT);
            }
        }

        return parent::render();
    }
}
