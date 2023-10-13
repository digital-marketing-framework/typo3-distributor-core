<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue;

use DateTime;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository implements QueueInterface
{
    protected int $pid;

    protected function getPid(): int
    {
        if (!isset($this->pid)) {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            try {
                $this->pid = $extensionConfiguration->get('dmf_distributor_core')['queue']['pid'] ?? 0;
            } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
                $this->pid = 0;
            }
        }

        return $this->pid;
    }

    /**
     * @param array<int> $status
     *
     * @return array<Job>
     */
    protected function fetchWhere(array $status = [], int $limit = 0, int $offset = 0, int $minTimeSinceChangedInSeconds = 0, int $minAgeInSeconds = 0): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(true);
        $query->getQuerySettings()->setStoragePageIds([$this->getPid()]);

        $conditions = [];
        if ($status !== []) {
            $conditions[] = $query->in('status', $status);
        }

        if ($minTimeSinceChangedInSeconds > 0) {
            $then = new DateTime();
            $then->modify('- ' . $minTimeSinceChangedInSeconds . ' seconds');
            $conditions[] = $query->lessThan('changed', $then);
        }

        if ($minAgeInSeconds > 0) {
            $then = new DateTime();
            $then->modify('- ' . $minAgeInSeconds . ' seconds');
            $conditions[] = $query->lessThan('created', $then);
        }

        if ($conditions !== []) {
            $typo3Version = new Typo3Version();
            if ($typo3Version->getMajorVersion() <= 11) {
                $query->matching($query->logicalAnd($conditions)); // @phpstan-ignore-line TYPO3 version switch
            } else {
                $query->matching($query->logicalAnd(...$conditions)); // @phpstan-ignore-line TYPO3 version switch
            }
        }

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        if ($offset > 0) {
            $query->setOffset($offset);
        }

        return $query->execute()->toArray();
    }

    public function fetch(array $status = [], int $limit = 0, int $offset = 0): array
    {
        return $this->fetchWhere($status, $limit, $offset);
    }

    public function fetchQueued(int $limit = 0, int $offset = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_QUEUED], $limit, $offset);
    }

    public function fetchPending(int $limit = 0, int $offset = 0, int $minTimeSinceChangedInSeconds = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_PENDING], $limit, $offset, $minTimeSinceChangedInSeconds);
    }

    public function fetchRunning(int $limit = 0, int $offset = 0, int $minTimeSinceChangedInSeconds = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_RUNNING], $limit, $offset, $minTimeSinceChangedInSeconds);
    }

    public function fetchPendingAndRunning(int $limit = 0, int $offset = 0, int $minTimeSinceChangedInSeconds = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_PENDING, QueueInterface::STATUS_RUNNING], $limit, $offset, $minTimeSinceChangedInSeconds);
    }

    public function fetchDone(int $limit = 0, int $offset = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_DONE], $limit, $offset);
    }

    public function fetchFailed(int $limit = 0, int $offset = 0): array
    {
        return $this->fetchWhere([QueueInterface::STATUS_FAILED], $limit, $offset);
    }

    public function markAs(JobInterface $job, int $status, string $message = '', bool $skipped = false): void
    {
        if (!$job instanceof Job) {
            throw new DigitalMarketingFrameworkException(sprintf('Foreign job object "%s" cannot be updated in this queue.', $job::class));
        }

        $job->setStatus($status);
        $job->setChanged(new DateTime());
        $job->setStatusMessage($message);
        $job->setSkipped($skipped);
        $this->update($job);
        $this->persistenceManager->persistAll();
    }

    public function markAsQueued(JobInterface $job): void
    {
        $this->markAs($job, QueueInterface::STATUS_QUEUED);
    }

    public function markAsPending(JobInterface $job): void
    {
        $this->markAs($job, QueueInterface::STATUS_PENDING);
    }

    public function markAsRunning(JobInterface $job): void
    {
        $this->markAs($job, QueueInterface::STATUS_RUNNING);
    }

    public function markAsDone(JobInterface $job, bool $skipped = false): void
    {
        $this->markAs($job, QueueInterface::STATUS_DONE, '', $skipped);
    }

    public function markAsFailed(JobInterface $job, string $message = ''): void
    {
        $this->markAs($job, QueueInterface::STATUS_FAILED, $message);
    }

    public function markListAsQueued(array $jobs): void
    {
        foreach ($jobs as $job) {
            $this->markAsQueued($job);
        }
    }

    public function markListAsPending(array $jobs): void
    {
        foreach ($jobs as $job) {
            $this->markAsPending($job);
        }
    }

    public function markListAsRunning(array $jobs): void
    {
        foreach ($jobs as $job) {
            $this->markAsRunning($job);
        }
    }

    public function markListAsDone(array $jobs, bool $skipped = false): void
    {
        foreach ($jobs as $job) {
            $this->markAsDone($job, $skipped);
        }
    }

    public function markListAsFailed(array $jobs, string $message = ''): void
    {
        foreach ($jobs as $job) {
            $this->markAsFailed($job, $message);
        }
    }

    protected function convertJobForRepository(JobInterface $job): Job
    {
        if (!$job instanceof Job) {
            $newJob = new Job();
            $newJob->setData($job->getData());
            $newJob->setCreated($job->getCreated());
            $newJob->setChanged($job->getChanged());
            $newJob->setStatus($job->getStatus());
            $newJob->setStatusMessage($job->getStatusMessage());
            $newJob->setSkipped($job->getSkipped());
            $newJob->setHash($job->getHash());
            $newJob->setLabel($job->getLabel());
            $job = $newJob;
        }

        return $job;
    }

    public function addJob(JobInterface $job): JobInterface
    {
        $job = $this->convertJobForRepository($job);
        $job->setPid($this->getPid());
        $this->add($job);
        $this->persistenceManager->persistAll();

        return $job;
    }

    public function removeJob(JobInterface $job): void
    {
        if (!$job instanceof Job) {
            throw new DigitalMarketingFrameworkException(sprintf('Foreign job object "%s" cannot be removed from this queue.', $job::class));
        }

        $this->remove($job);
        $this->persistenceManager->persistAll();
    }

    public function removeOldJobs(int $minAgeInSeconds, array $status = []): void
    {
        $jobs = $this->fetchWhere($status, 0, 0, 0, $minAgeInSeconds);
        foreach ($jobs as $job) {
            $this->remove($job);
        }

        $this->persistenceManager->persistAll();
    }
}
