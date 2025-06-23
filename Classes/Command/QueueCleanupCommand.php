<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Command;

use DigitalMarketingFramework\Core\Queue\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCleanupCommand extends QueueCommand
{
    protected function configure(): void
    {
        $this->addOption('done-only', 'd', InputOption::VALUE_NONE, 'Done only');
        $this->setHelp('Process queued Anyrel distribution jobs.');
    }

    protected function doneOnly(InputInterface $input): bool
    {
        return (bool)$input->getOption('done-only');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->prepareTask();

        $expirationTime = $this->queueSettings->getExpirationTime();
        $status = $this->doneOnly($input) ? [QueueInterface::STATUS_DONE] : [];
        $this->queue->removeOldJobs($expirationTime, $status);

        $output->writeln('Anyrel distribution queue cleanup executed successfully.');

        return Command::SUCCESS;
    }
}
