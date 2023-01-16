<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

class QueueProcessorFieldProvider extends QueueFieldProvider
{
    /**
     * @param ?QueueProcessorTask $task
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject): array
    {
        $additionalFields = [];

        if ($task !== null) {
            $taskInfo['batchSize'] = $task->getBatchSize();
        } else {
            $taskInfo['batchSize'] = QueueProcessorTask::BATCH_SIZE;
        }

        $this->addField($additionalFields, $taskInfo, 'batchSize', 'Batch size of jobs to process per run');

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
    {
        $submittedData['batchSize'] = (int)$submittedData['batchSize'];
        return true;
    }

    /**
     * @param ?QueueProcessorTask $task
     */
    public function saveAdditionalFields(array $submittedData, $task): void
    {
        $task->setBatchSize((int)$submittedData['batchSize']);
    }
}
