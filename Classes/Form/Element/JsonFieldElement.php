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
        // If value can be decoded into json, we encode it again with JSON_PRETTY_PRINT
        $itemValue = json_decode((string)$this->data['parameterArray']['itemFormElValue'], null, 512, JSON_THROW_ON_ERROR);
        if ((bool)$itemValue) {
            $this->data['parameterArray']['itemFormElValue'] = json_encode($itemValue, JSON_PRETTY_PRINT);
        }

        return parent::render();
    }
}
