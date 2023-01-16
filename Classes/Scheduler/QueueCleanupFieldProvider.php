<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

class QueueCleanupFieldProvider extends QueueFieldProvider
{
    /**
     * @param ?QueueCleanupTask $task
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject): array
    {
        $additionalFields = [];

        if ($task) {
            $taskInfo['doneOnly'] = $task->getDoneOnly() ? 1 : 0;
        } else {
            $taskInfo['doneOnly'] = 0;
        }

        $this->addCheckboxField($additionalFields, $taskInfo, 'doneOnly', 'Delete only jobs with status "done"');

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
    {
        $submittedData['doneOnly'] = isset($submittedData['doneOnly']) ? (bool)$submittedData['doneOnly'] : false;
        return true;
    }

    /**
     * @param ?QueueCleanupTask $task
     */
    public function saveAdditionalFields(array $submittedData, $task): void
    {
        $task->setDoneOnly((bool)$submittedData['doneOnly']);
    }
}
