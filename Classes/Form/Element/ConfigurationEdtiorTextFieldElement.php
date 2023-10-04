<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Form\Element;

use DigitalMarketingFramework\Typo3\Core\Utility\ConfigurationEditorRenderUtility;
use DigitalMarketingFramework\Typo3\Core\Utility\VendorAssetUtility;
use DOMDocument;
use DOMElement;
use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

class ConfigurationEdtiorTextFieldElement extends TextElement
{
    public function render(): array
    {
        $resultArray = parent::render();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $readonly = $config['readOnly'] ?? false;

        $doc = new DOMDocument();
        $doc->loadHTML($resultArray['html'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $textareas = $doc->getElementsByTagName('textarea');
        if ($textareas->length === 1) {
            /** @var DOMElement $textarea */
            $textarea = $textareas->item(0);
            $class = $textarea->getAttribute('class');
            if ($class !== '') {
                $class .= ' ';
            }
            $textarea->setAttribute('class', $class . 'dmf-configuration-document');
            $attributes = ConfigurationEditorRenderUtility::getTextAreaDataAttributes(ready:true, mode:'modal', readonly:$readonly, globalDocument:false);
            foreach ($attributes as $name => $value) {
                $textarea->setAttribute('data-' . $name, $value);
            }
            $textarea->setAttribute('data-app-script', VendorAssetUtility::makeVendorAssetAvailable('digital-marketing-framework/core', '/scripts/index.js'));
            $textarea->setAttribute('data-app-styles', VendorAssetUtility::makeVendorAssetAvailable('digital-marketing-framework/core', '/styles/index.css'));
        }
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@digital-marketing-framework/typo3-distributor-core/load-configuration-editor.js');
        $resultArray['html'] = $doc->saveHTML();

        return $resultArray;
    }
}
