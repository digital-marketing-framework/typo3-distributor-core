<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Hooks;

use DigitalMarketingFramework\Typo3\Core\Form\Element\ConfigurationEditorTextFieldElement;

class FlexFormHook
{
    /**
     * @param array<string,mixed> $dataStructure
     * @param array<string,mixed> $identifier
     *
     * @return array<string,mixed>
     */
    public function parseDataStructureByIdentifierPostProcess(array $dataStructure, array $identifier): array
    {
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return $dataStructure;
        }

        foreach ($dataStructure['sheets'] as $sheetKey => $sheet) {
            if ($sheetKey === 'sDEF') {
                continue;
            }

            if (isset($sheet['ROOT']['el']['settings.finishers.Digitalmarketingframework.setup'])) {
                $dataStructure['sheets'][$sheetKey]['ROOT']['el']['settings.finishers.Digitalmarketingframework.setup']['TCEforms']['config']['renderType'] = ConfigurationEditorTextFieldElement::RENDER_TYPE;
            }
        }

        return $dataStructure;
    }
}
