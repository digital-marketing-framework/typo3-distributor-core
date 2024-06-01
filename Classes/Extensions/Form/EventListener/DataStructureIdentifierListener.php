<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\EventListener;

use DigitalMarketingFramework\Typo3\Core\Form\Element\ConfigurationEditorTextFieldElement;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Event listener extending the flex form handling for tt_content form elements (CType: form_formframework):
 *
 * * Adds configuration editor functionality for DMF finisher
 *
 * Scope: backend
 *
 * @internal
 */
class DataStructureIdentifierListener
{
    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return;
        }

        $dataStructure = $event->getDataStructure();
        foreach ($dataStructure['sheets'] as $sheetKey => $sheet) {
            if ($sheetKey === 'sDEF') {
                continue;
            }

            if (isset($sheet['ROOT']['el']['settings.finishers.Digitalmarketingframework.setup'])) {
                $dataStructure['sheets'][$sheetKey]['ROOT']['el']['settings.finishers.Digitalmarketingframework.setup']['config']['renderType'] = ConfigurationEditorTextFieldElement::RENDER_TYPE;
            }
        }

        $event->setDataStructure($dataStructure);
    }
}
