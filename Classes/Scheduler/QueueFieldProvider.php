<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;

abstract class QueueFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * @param array<string,array{code:string,label:string,cshKey?:string,cshLabel?:string}> $additionalFields
     * @param array<string,mixed> $taskInfo
     */
    protected function addField(array &$additionalFields, array $taskInfo, string $fieldName, string $label): void
    {
        $fieldId = 'task_' . $fieldName;
        $fieldCode = '<input type="text" name="tx_scheduler[' . $fieldName . ']"'
            . ' id="' . $fieldId . '"'
            . ' value="' . $taskInfo[$fieldName] . '"'
            . ' size="30" />';
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label,
        ];
    }

    /**
     * @param array<string,array{code:string,label:string,cshKey?:string,cshLabel?:string}> $additionalFields
     * @param array<string,mixed> $taskInfo
     */
    protected function addCheckboxField(array &$additionalFields, array $taskInfo, string $fieldName, string $label): void
    {
        $fieldId = 'task_' . $fieldName;
        $fieldCode = '<input type="checkbox" name="tx_scheduler[' . $fieldName . ']"'
            . ' id="' . $fieldId . '"'
            . ((bool)$taskInfo[$fieldName] ? ' checked="checked"' : '')
            . ' value="1" />';
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label,
        ];
    }
}
