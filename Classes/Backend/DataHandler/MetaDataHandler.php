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
        $serializedData = json_decode($fieldArray['serialized_data'] ?? '', null, 512, JSON_THROW_ON_ERROR);
        if (!(bool)$serializedData) {
            $job->setSerializedData('');
        } else {
            $job->setSerializedData(json_encode($serializedData, JSON_THROW_ON_ERROR));
        }

        $job->setHash($fieldArray['hash'] ?? '');

        $fieldArray['serialized_data'] = json_encode(json_decode($job->getSerializedData(), null, 512, JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR);

        $job->setHash($this->queueDataFactory->getJobHash($job));
        $fieldArray['hash'] = $job->getHash();

        $label = $this->queueDataFactory->getJobLabel($job);
        $job->setLabel($label);
        if ($label !== 'undefined' || !(bool)$fieldArray['label']) {
            $fieldArray['label'] = $job->getLabel();
        }
        if ($label !== 'undefined' || !(bool)$fieldArray['type']) {
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
