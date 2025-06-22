<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Notification\NotificationManagerInterface;
use DigitalMarketingFramework\Core\Queue\QueueProcessorInterface;
use DigitalMarketingFramework\Distributor\Core\Service\DistributorInterface;

/**
 * @deprecated
 */
class QueueProcessorTask extends QueueTask
{
    /**
     * @var int
     */
    public const BATCH_SIZE = 10;

    protected int $batchSize = self::BATCH_SIZE;

    protected QueueProcessorInterface $queueProcessor;

    protected DistributorInterface $distributor;

    protected NotificationManagerInterface $notificationManager;

    protected function prepareTask(): void
    {
        parent::prepareTask();
        $this->notificationManager = $this->registry->getNotificationManager();
        $this->distributor = $this->registry->getDistributor();
        $this->queueProcessor = $this->registry->getQueueProcessor($this->queue, $this->distributor);
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    public function execute(): bool
    {
        $this->prepareTask();

        $componentLevel = $this->notificationManager->pushComponent('distributor');
        $this->queueProcessor->updateJobsAndProcessBatch($this->batchSize);
        $this->notificationManager->popComponent($componentLevel);

        return true;
    }
}
