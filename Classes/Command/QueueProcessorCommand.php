<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Command;

use DigitalMarketingFramework\Core\Notification\NotificationManagerInterface;
use DigitalMarketingFramework\Core\Queue\QueueProcessorInterface;
use DigitalMarketingFramework\Distributor\Core\Service\DistributorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueProcessorCommand extends QueueCommand
{
    public const DEFAULT_BATCH_SIZE = 10;

    protected QueueProcessorInterface $queueProcessor;

    protected DistributorInterface $distributor;

    protected NotificationManagerInterface $notificationManager;

    protected function configure(): void
    {
        $this->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Batch size', static::DEFAULT_BATCH_SIZE);
        $this->setHelp('Process queued Anyrel distribution jobs.');
    }

    protected function prepareTask(): void
    {
        parent::prepareTask();
        $this->notificationManager = $this->registry->getNotificationManager();
        $this->distributor = $this->registry->getDistributor();
        $this->queueProcessor = $this->registry->getQueueProcessor($this->queue, $this->distributor);
    }

    protected function getBatchSize(InputInterface $input): int
    {
        return (int)$input->getOption('batch-size');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->prepareTask();
        $batchSize = $this->getBatchSize($input);

        $componentLevel = $this->notificationManager->pushComponent('distributor');
        $this->queueProcessor->updateJobsAndProcessBatch($batchSize);
        $this->notificationManager->popComponent($componentLevel);

        $output->writeln('Anyrel distribution queue processor executed successfully.');

        return Command::SUCCESS;
    }
}
