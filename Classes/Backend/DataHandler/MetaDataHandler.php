<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\DataHandler;

use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactoryInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaDataHandler implements SingletonInterface
{
    protected ?RegistryCollection $registryCollection = null;

    protected ?RegistryInterface $registry = null;

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
            if (!$this->registryCollection instanceof RegistryCollection) {
                $this->registryCollection = GeneralUtility::makeInstance(RegistryCollection::class);
                $this->registry = $this->registryCollection->getRegistryByClass(RegistryInterface::class);
                $this->queueDataFactory = $this->registry->getQueueDataFactory();
            }

            $this->updateJobData($fieldArray);
        }
    }
}
