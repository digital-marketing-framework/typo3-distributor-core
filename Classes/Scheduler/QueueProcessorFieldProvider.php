<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class QueueProcessorFieldProvider extends QueueFieldProvider
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

        $taskInfo['batchSize'] = $task instanceof QueueProcessorTask ? $task->getBatchSize() : QueueProcessorTask::BATCH_SIZE;

        $this->addField($additionalFields, $taskInfo, 'batchSize', 'Batch size of jobs to process per run');

        return $additionalFields;
    }

    /**
     * @param array<mixed> $submittedData
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
    {
        $submittedData['batchSize'] = (int)$submittedData['batchSize'];

        return true;
    }

    /**
     * @param array<mixed> $submittedData
     * @param ?AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, $task): void
    {
        if (!$task instanceof QueueProcessorTask) {
            throw new DigitalMarketingFrameworkException(sprintf('Scheduler task QueueProcessorTask expected but "%s" found.', $task::class));
        }

        $task->setBatchSize((int)$submittedData['batchSize']);
    }
}
