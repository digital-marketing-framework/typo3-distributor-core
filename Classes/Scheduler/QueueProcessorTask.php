<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Queue\QueueProcessorInterface;
use DigitalMarketingFramework\Distributor\Core\Service\DistributorInterface;

class QueueProcessorTask extends QueueTask
{
    /**
     * @var int
     */
    public const BATCH_SIZE = 10;

    protected int $batchSize = self::BATCH_SIZE;

    protected QueueProcessorInterface $queueProcessor;

    protected DistributorInterface $relay;

    protected function prepareTask(): void
    {
        parent::prepareTask();
        $this->relay = $this->registry->getDistributor();
        $this->queueProcessor = $this->registry->getQueueProcessor($this->queue, $this->relay);
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
        $this->queueProcessor->processBatch($this->batchSize);

        return true;
    }
}
