<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Scheduler;

use DigitalMarketingFramework\Core\Queue\QueueInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueCleanupTask extends QueueTask
{
    protected const DEFAULT_EXPIRATION_TIME = 30;

    protected bool $doneOnly = false;

    public function getDoneOnly(): bool
    {
        return $this->doneOnly;
    }

    public function setDoneOnly(bool $doneOnly): void
    {
        $this->doneOnly = $doneOnly;
    }

    protected function getExtensionQueueSettings(): array
    {
        try {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            return $extensionConfiguration->get('digitalmarketingframework_distributor')['queue'] ?? [];
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return static::DEFAULT_EXPIRATION_TIME;
        }
    }

    protected function getExpirationTime(): int
    {
        $expirationInDays = $this->getExtensionQueueSettings()['expirationTime'] ?? static::DEFAULT_EXPIRATION_TIME;
        return $expirationInDays * 24 * 3600;
    }

    public function execute(): bool
    {
        $this->prepareTask();
        $this->queue->removeOldJobs(
            $this->getExpirationTime(),
            $this->doneOnly ? [QueueInterface::STATUS_DONE] : []
        );
        return true;
    }
}
