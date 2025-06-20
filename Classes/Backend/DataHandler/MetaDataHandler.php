<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\DataHandler;

use DigitalMarketingFramework\Core\Model\Queue\Job;
use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactoryInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaDataHandler implements SingletonInterface
{
    protected ?QueueDataFactoryInterface $queueDataFactory = null;

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
            if (!$this->queueDataFactory instanceof QueueDataFactoryInterface) {
                $registryCollection = GeneralUtility::makeInstance(RegistryCollection::class);
                $registry = $registryCollection->getRegistryByClass(RegistryInterface::class);
                $this->queueDataFactory = $registry->getQueueDataFactory();
            }

            $this->updateJobData($fieldArray);
        }
    }
}
