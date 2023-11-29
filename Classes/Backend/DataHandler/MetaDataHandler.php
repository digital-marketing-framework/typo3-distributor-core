<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\DataHandler;

use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactoryInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

class MetaDataHandler implements SingletonInterface
{
    protected QueueDataFactoryInterface $queueDataFactory;

    public function __construct(
        protected Registry $registry,
    ) {
        $this->queueDataFactory = $this->registry->getQueueDataFactory();
    }

    /**
     * @param array<string,mixed> $fieldArray
     */
    protected function updateJobData(array &$fieldArray): void
    {
        $job = new Job();
        $job->setSerializedData($fieldArray['serialized_data']);

        $hash = $this->queueDataFactory->getJobHash($job);
        $job->setHash($hash);
        if ($hash !== 'undefined') {
            $fieldArray['hash'] = $job->getHash();
        }

        $label = $this->queueDataFactory->getJobLabel($job);
        $job->setLabel($label);
        if ($label !== 'undefined') {
            $fieldArray['label'] = $job->getLabel();
        }

        if ($label !== 'undefined') {
            $fieldArray['type'] = $job->getType();
        }
    }

    /**
     * @param array<string,mixed> $fieldArray
     */
    public function processDatamap_preProcessFieldArray(array &$fieldArray, string $table, string $id, DataHandler $parentObj): void
    {
        if (($table === 'tx_dmfdistributorcore_domain_model_queue_job') && !$parentObj->isImporting) {
            $this->updateJobData($fieldArray);
        }
    }
}
