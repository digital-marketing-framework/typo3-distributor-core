<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\DataHandler;

use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactoryInterface;
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

    protected function updateJobData(&$fieldArray)
    {
        $job = new Job();
        $serializedData = json_decode($fieldArray['serialized_data'] ?? '');
        if (!$serializedData) {
            $job->setSerializedData('');
        } else {
            $job->setSerializedData(json_encode($serializedData));
        }
        $job->setHash($fieldArray['hash'] ?? '');

        $fieldArray['serialized_data'] = json_encode(json_decode($job->getSerializedData()));
        $fieldArray['route'] = $this->queueDataFactory->getJobRoute($job);
        $fieldArray['pass'] = $this->queueDataFactory->getJobRoutePass($job);

        $job->setHash($this->queueDataFactory->getJobHash($job));
        $fieldArray['hash'] = $job->getHash();

        $job->setLabel($this->queueDataFactory->getJobLabel($job));
        $fieldArray['label'] = $job->getLabel();
    }

    public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, DataHandler $parentObj)
    {
        if (($table === 'tx_digitalmarketingframeworkdistributor_domain_model_queue_job') && !$parentObj->isImporting) {
            $this->updateJobData($fieldArray);
        }
    }
}
