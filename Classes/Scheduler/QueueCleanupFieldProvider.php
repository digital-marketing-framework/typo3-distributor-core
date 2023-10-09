<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class QueueCleanupFieldProvider extends QueueFieldProvider
{
    /**
     * @param array<mixed> $taskInfo
     * @param ?AbstractTask $task
     *
     * @return array<string,array{code:string,label:string,cshKey?:string,cshLabel?:string}>
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject): array
    {
        $additionalFields = [];

        if ($task instanceof QueueCleanupTask) {
            $taskInfo['doneOnly'] = $task->getDoneOnly() ? 1 : 0;
        } else {
            $taskInfo['doneOnly'] = 0;
        }

        $this->addCheckboxField($additionalFields, $taskInfo, 'doneOnly', 'Delete only jobs with status "done"');

        return $additionalFields;
    }

    /**
     * @param array<mixed> $submittedData
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
    {
        $submittedData['doneOnly'] = isset($submittedData['doneOnly']) && (bool)$submittedData['doneOnly'];

        return true;
    }

    /**
     * @param array<mixed> $submittedData
     * @param ?AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, $task): void
    {
        if (!$task instanceof QueueCleanupTask) {
            throw new DigitalMarketingFrameworkException(sprintf('Scheduler task QueueCleanupTask expected but "%s" found.', $task::class));
        }

        $task->setDoneOnly((bool)$submittedData['doneOnly']);
    }
}
