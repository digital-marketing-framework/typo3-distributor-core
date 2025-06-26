<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Command;

use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueProcessorCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('Process queued Anyrel distribution jobs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registryCollection = GeneralUtility::makeInstance(RegistryCollection::class);
        $registry = $registryCollection->getRegistryByClass(RegistryInterface::class);

        $queueProcessor = $registry->getQueueProcessor(
            $registry->getPersistentQueue(),
            $registry->getDistributor()
        );
        $queueProcessor->updateJobsAndProcessBatch();

        $output->writeln('Anyrel distribution queue processor executed successfully.');

        return Command::SUCCESS;
    }
}
