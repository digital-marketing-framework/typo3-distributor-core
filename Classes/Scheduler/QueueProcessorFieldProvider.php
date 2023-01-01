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
            $taskInfo['pid'] = $task->getPid();
            $taskInfo['batchSize'] = $task->getBatchSize();
        } else {
            $taskInfo['pid'] = 0;
            $taskInfo['batchSize'] = QueueProcessorTask::BATCH_SIZE;
        }

        $this->addField($additionalFields, $taskInfo, 'pid', 'ID of the folder that contains the submission jobs.');
        $this->addField($additionalFields, $taskInfo, 'batchSize', 'Batch size of jobs to process per run');

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
    {
        $submittedData['pid'] = (int)$submittedData['pid'];
        $submittedData['batchSize'] = (int)$submittedData['batchSize'];
        return true;
    }

    /**
     * @param ?QueueProcessorTask $task
     */
    public function saveAdditionalFields(array $submittedData, $task): void
    {
        $task->setPid((int)$submittedData['pid']);
        $task->setBatchSize((int)$submittedData['batchSize']);
    }
}
