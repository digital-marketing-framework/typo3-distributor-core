<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue;

use DateTime;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Model\Queue\Error;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Schema\QueueSchema;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository implements QueueInterface
{
    protected int $pid;

    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    protected function getPid(): int
    {
        if (!isset($this->pid)) {
            try {
                $config = $this->extensionConfiguration->get('dmf_distributor_core');
                $this->pid = $config[QueueSchema::KEY_QUEUE][QueueSchema::KEY_QUEUE_PID] ?? 0;
            } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
                $this->pid = 0;
            }
        }

        return $this->pid;
    }

    // QUERY BUILDER PART START
    // the following query methods use the custom QueryBuilder so that they are able to execute COUNT(*) statements combined with GROUP BY statements
    // the result is a custom row and cannot be converted into an extbase model

    /**
     * @param array{minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime} $filters
     *
     * @return array<string>
     */
    protected function getTimeframeConditionsForCustomQuery(QueryBuilder $query, array $filters): array
    {
        $conditions = [];
        if ($filters['minChanged'] instanceof DateTime) {
            $conditions[] = $query->expr()->gte('changed', $query->createNamedParameter($filters['minChanged']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($filters['maxChanged'] instanceof DateTime) {
            $conditions[] = $query->expr()->lte('changed', $query->createNamedParameter($filters['maxChanged']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($filters['minCreated'] instanceof DateTime) {
            $conditions[] = $query->expr()->gte('created', $query->createNamedParameter($filters['minCreated']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($filters['maxCreated'] instanceof DateTime) {
            $conditions[] = $query->expr()->lte('created', $query->createNamedParameter($filters['maxCreated']->getTimestamp(), Connection::PARAM_INT));
        }

        return $conditions;
    }

    /**
     * @param array{minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime} $filters
     *
     * @return array{hashes:int,all:int,queued:int,pending:int,running:int,done:int,doneNotSkipped:int,doneSkipped:int,failed:int,groupedByType:array<string,array{all:int,queued:int,pending:int,running:int,done:int,doneNotSkipped:int,doneSkipped:int,failed:int}>}
     */
    public function getStatistics(array $filters): array
    {
        $result = [
            'hashes' => 42,
            'all' => 0,
            'queued' => 0,
            'pending' => 0,
            'running' => 0,
            'done' => 0,
            'doneNotSkipped' => 0,
            'doneSkipped' => 0,
            'failed' => 0,
            'groupedByType' => [],
        ];

        $query = $this->connectionPool->getQueryBuilderForTable('tx_dmfdistributorcore_domain_model_queue_job');
        $query
            ->selectLiteral('COUNT(DISTINCT(hash)) as count')
            ->from('tx_dmfdistributorcore_domain_model_queue_job');
        $conditions = $this->getTimeframeConditionsForCustomQuery($query, $filters);
        if ($conditions !== []) {
            $query->where(...$conditions);
        }

        $queryResult = $query->executeQuery();
        $row = $queryResult->fetchAssociative();
        $result['hashes'] = $row['count'];

        $query = $this->connectionPool->getQueryBuilderForTable('tx_dmfdistributorcore_domain_model_queue_job');
        $query
            ->count('*')
            ->addSelect('type', 'status', 'skipped')
            ->from('tx_dmfdistributorcore_domain_model_queue_job')
            ->groupBy('type', 'status', 'skipped');
        $conditions = $this->getTimeframeConditionsForCustomQuery($query, $filters);
        if ($conditions !== []) {
            $query->where(...$conditions);
        }

        $queryResult = $query->executeQuery();
        while ($row = $queryResult->fetchAssociative()) {
            $count = $row['COUNT(*)'];
            $type = $row['type'];
            if (!isset($result['groupedByType'][$type])) {
                $result['groupedByType'][$type] = [
                    'all' => 0,
                    'queued' => 0,
                    'pending' => 0,
                    'running' => 0,
                    'done' => 0,
                    'doneNotSkipped' => 0,
                    'doneSkipped' => 0,
                    'failed' => 0,
                ];
            }

            $result['all'] += $count;
            $result['groupedByType'][$type]['all'] += $count;
            switch ($row['status']) {
                case QueueInterface::STATUS_QUEUED:
                    $result['queued'] += $count;
                    $result['groupedByType'][$type]['queued'] += $count;
                    break;
                case QueueInterface::STATUS_PENDING:
                    $result['pending'] += $count;
                    $result['groupedByType'][$type]['pending'] += $count;
                    break;
                case QueueInterface::STATUS_RUNNING:
                    $result['running'] += $count;
                    $result['groupedByType'][$type]['running'] += $count;
                    break;
                case QueueInterface::STATUS_DONE:
                    $result['done'] += $count;
                    $result['groupedByType'][$type]['done'] += $count;

                    $group = (bool)$row['skipped'] ? 'doneSkipped' : 'doneNotSkipped';
                    $result[$group] += $count;
                    $result['groupedByType'][$type][$group] += $count;
                    break;
                case QueueInterface::STATUS_FAILED:
                    $result['failed'] += $count;
                    $result['groupedByType'][$type]['failed'] += $count;
                    break;
            }
        }

        return $result;
    }

    /**
     * @return array<string>
     */
    public function getJobTypes(): array
    {
        $query = $this->connectionPool->getQueryBuilderForTable('tx_dmfdistributorcore_domain_model_queue_job');
        $query = $this->connectionPool->getQueryBuilderForTable('tx_dmfdistributorcore_domain_model_queue_job');
        $query
            ->select('type')
            ->from('tx_dmfdistributorcore_domain_model_queue_job')
            ->groupBy('type');

        $result = $query->executeQuery()->fetchAllAssociative();

        return array_map(static fn (array $row) => $row['type'], $result);
    }

    /**
     * @param array{minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime} $filters
     * @param array{page:int,itemsPerPage:int,sorting:array<string,string>} $navigation
     *
     * @return array<Error>
     */
    public function getErrorMessages(array $filters, array $navigation): array
    {
        // TODO filter by type?

        $query = $this->connectionPool->getQueryBuilderForTable('tx_dmfdistributorcore_domain_model_queue_job');
        $query->count('*')
            ->addSelect('type', 'status_message')
            ->from('tx_dmfdistributorcore_domain_model_queue_job')
            ->groupBy('status_message', 'type');
        $conditions = $this->getTimeframeConditionsForCustomQuery($query, $filters);
        $conditions[] = $query->expr()->eq('status', $query->createNamedParameter(QueueInterface::STATUS_FAILED, Connection::PARAM_INT));
        $query->where(...$conditions);
        $queryResult = $query->executeQuery();

        $result = [];
        while ($row = $queryResult->fetchAssociative()) {
            /** @var string */
            $message = $row['status_message'];
            /** @var int */
            $count = $row['COUNT(*)'];
            $result[$message] ??= [
                'message' => $message,
                'count' => 0,
                'types' => [],
            ];
            $result[$message]['count'] += $count;
            $result[$message]['types'][$row['type']] = $count;
        }

        $messages = array_keys($result);
        foreach ($messages as $message) {
            $lastSeen = $this->findOneByErrorMessage($message, lastSeen: true, filters: $filters);
            if (!$lastSeen instanceof Job) {
                throw new DigitalMarketingFrameworkException('cannot load job "lastSeen" for error statistics', 8748465510);
            }

            $result[$message]['lastSeen'] = $lastSeen;

            $firstSeen = $this->findOneByErrorMessage($message, lastSeen: false, filters: $filters);
            if (!$firstSeen instanceof Job) {
                throw new DigitalMarketingFrameworkException('cannot load job "firstSeen" for error statistics', 4837789032);
            }

            $result[$message]['firstSeen'] = $firstSeen;
        }

        $result = array_values($result);
        if ($navigation['sorting'] !== []) {
            usort($result, static function (array $row1, array $row2) use ($navigation) {
                $sortDirection = 'DESC';
                $value1 = 0;
                $value2 = 0;
                foreach ($navigation['sorting'] as $sort => $direction) {
                    if ($direction === '') {
                        continue;
                    }

                    $sortDirection = $direction;
                    $value1 = match ($sort) {
                        'count' => $row1['count'],
                        'lastSeen' => $row1['lastSeen']->getChanged()->getTimestamp(),
                        'firstSeen' => $row1['firstSeen']->getChanged()->getTimestamp(),
                        default => throw new DigitalMarketingFrameworkException(sprintf('unknown sort attribute "%s"', $sort), 6991592528),
                    };
                    $value2 = match ($sort) {
                        'count' => $row2['count'],
                        'lastSeen' => $row2['lastSeen']->getChanged()->getTimestamp(),
                        'firstSeen' => $row2['firstSeen']->getChanged()->getTimestamp(),
                        default => throw new DigitalMarketingFrameworkException(sprintf('unknown sort attribute "%s"', $sort), 8729504902),
                    };
                    if ($value1 !== $value2) {
                        break;
                    }
                }

                return $sortDirection === 'ASC' ? $value1 <=> $value2 : $value2 <=> $value1;
            });
        }

        if ($navigation['itemsPerPage'] > 0) {
            $limit = $navigation['itemsPerPage'];
            $offset = $navigation['itemsPerPage'] * $navigation['page'];
            $result = array_slice($result, $offset, $limit);
        }

        return array_map(static fn (array $data) => Error::fromDataRecord($data), $result);
    }

    // QUERY BUILDER PART END
    // the rest of the query methods will use standard extbase queries again

    /**
     * @param array{minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime} $filters
     */
    public function findOneByErrorMessage(string $message, bool $lastSeen, array $filters): ?Job
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(true);
        $query->getQuerySettings()->setStoragePageIds([$this->getPid()]);

        $filters['search'] = $message;
        $filters['searchExactMatch'] = true;
        $filters['advancedSearch'] = false;
        $filters['status'] = [QueueInterface::STATUS_FAILED];
        $filters['type'] = [];
        $filters['skipped'] = null;
        $this->applyFilters($query, $filters, ['status_message']);

        $navigation = [
            'page' => 0,
            'itemsPerPage' => 1,
            'sorting' => [
                'changed' => $lastSeen ? 'DESC' : 'ASC',
            ],
        ];
        $this->applyNavigation($query, $navigation);

        $results = $query->execute()->toArray();

        return $results !== [] ? reset($results) : null;
    }

    public function fetchByIdList(array $ids): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $ids));

        return $query->execute()->toArray();
    }

    protected function buildTextQueryString(string $phrase): string
    {
        $search = $phrase;

        $search = str_replace('\\\\', '__CHAR_BACK_SLASH__', $search);
        $search = str_replace('\\*', '__CHAR_ASTERISK__', $search);
        $search = str_replace('\\%', '__CHAR_PERCENT__', $search);

        $search = str_replace('%', '\\%', $search);
        $search = str_replace('*', '%', $search);

        $search = str_replace('__CHAR_BACK_SLASH__', '\\\\', $search);
        $search = str_replace('__CHAR_ASTERISK__', '\\*', $search);
        $search = str_replace('__CHAR_PERCENT__', '\\%', $search);

        return '%' . trim($search, '%') . '%';
    }

    /**
     * @param QueryInterface<Job> $query
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     * @param array<string> $searchFields
     */
    protected function applyFilters(QueryInterface $query, array $filters, array $searchFields = ['label', 'type', 'hash', 'status_message']): void
    {
        $conditions = [];

        if ($filters['search'] !== '') {
            $search = $filters['searchExactMatch']
                ? $filters['search']
                : $this->buildTextQueryString($filters['search']);
            $fields = $searchFields;
            if ($filters['advancedSearch']) {
                $fields[] = 'serialized_data';
            }

            $subConditions = [];
            foreach ($fields as $field) {
                $searchCondition = $filters['searchExactMatch']
                    ? $query->equals($field, $search)
                    : $query->like($field, $search);
                if ($field !== 'status_message') {
                    $subConditions[] = $searchCondition;
                } else {
                    // status message will only be searched for failed jobs
                    $searchStatusConditions = [
                        $query->equals('status', QueueInterface::STATUS_FAILED),
                        $searchCondition,
                    ];
                    $subConditions[] = $query->logicalAnd(...$searchStatusConditions);
                }
            }

            $conditions[] = $query->logicalOr(...$subConditions);
        }

        if ($filters['minCreated'] instanceof DateTime) {
            $conditions[] = $query->greaterThanOrEqual('created', $filters['minCreated']);
        }

        if ($filters['maxCreated'] instanceof DateTime) {
            $conditions[] = $query->lessThanOrEqual('created', $filters['maxCreated']);
        }

        if ($filters['minChanged'] instanceof DateTime) {
            $conditions[] = $query->greaterThanOrEqual('changed', $filters['minChanged']);
        }

        if ($filters['maxChanged'] instanceof DateTime) {
            $conditions[] = $query->lessThanOrEqual('changed', $filters['maxChanged']);
        }

        if ($filters['type'] !== []) {
            $conditions[] = $query->in('type', $filters['type']);
        }

        if ($filters['status'] !== []) {
            $conditions[] = $query->in('status', $filters['status']);
        }

        if ($filters['skipped'] !== null) {
            // skipped flag will only be checked for finished jobs
            $skippedFinishedConditions = [
                $query->logicalNot($query->equals('status', QueueInterface::STATUS_DONE)),
                $query->equals('skipped', $filters['skipped'] ? 1 : 0),
            ];
            $conditions[] = $query->logicalOr(...$skippedFinishedConditions);
        }

        if ($conditions !== []) {
            $query->matching($query->logicalAnd(...$conditions));
        }
    }

    /**
     * @param QueryInterface<Job> $query
     * @param array{page:int,itemsPerPage:int,sorting:array<string,string>} $navigation
     */
    protected function applyNavigation(QueryInterface $query, array $navigation): void
    {
        if ($navigation['itemsPerPage'] > 0) {
            $query->setLimit($navigation['itemsPerPage']);
            if ($navigation['page'] > 0) {
                $query->setOffset($navigation['itemsPerPage'] * $navigation['page']);
            }
        }

        $sorting = array_filter($navigation['sorting'], static fn (string $sorting) => $sorting !== '');
        if ($sorting !== []) {
            $query->setOrderings(
                array_map(static fn (string $direction) => match ($direction) {
                    'ASC' => QueryInterface::ORDER_ASCENDING,
                    'DESC' => QueryInterface::ORDER_DESCENDING,
                    default => throw new DigitalMarketingFrameworkException(sprintf('unknown sort direction "%s"', $direction), 4158621568),
                }, $sorting)
            );
        }
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     * @param array{page:int,itemsPerPage:int,sorting:array<string,string>} $navigation
     *
     * @return array<Job>
     */
    public function fetchFiltered(array $filters, array $navigation): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(true);
        $query->getQuerySettings()->setStoragePageIds([$this->getPid()]);

        $this->applyFilters($query, $filters);
        $this->applyNavigation($query, $navigation);

        return $query->execute()->toArray();
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     */
    public function countFiltered(array $filters): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(true);
        $query->getQuerySettings()->setStoragePageIds([$this->getPid()]);

        $this->applyFilters($query, $filters);

        return $query->count();
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

        $query->setOrderings([
            'created' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

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
            $query->matching($query->logicalAnd(...$conditions));
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

    public function markAs(JobInterface $job, int $status, ?string $message = null, bool $skipped = false, bool $preserveTimestamp = false): void
    {
        if (!$job instanceof Job) {
            throw new DigitalMarketingFrameworkException(sprintf('Foreign job object "%s" cannot be updated in this queue.', $job::class), 2392968308);
        }

        $job->setStatus($status);
        $job->setSkipped($skipped);

        if ($message !== null) {
            $job->addStatusMessage($message);
        }

        if (!$preserveTimestamp) {
            $job->setChanged(new DateTime());
        }

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

    public function markAsFailed(JobInterface $job, string $message = '', bool $preserveTimestamp = false): void
    {
        $this->markAs($job, QueueInterface::STATUS_FAILED, $message, preserveTimestamp: $preserveTimestamp);
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

    public function markListAsFailed(array $jobs, string $message = '', bool $preserveTimestamp = false): void
    {
        foreach ($jobs as $job) {
            $this->markAsFailed($job, $message, $preserveTimestamp);
        }
    }

    protected function convertJobForRepository(JobInterface $job): Job
    {
        if (!$job instanceof Job) {
            $newJob = new Job();
            $newJob->setEnvironment($job->getEnvironment());
            $newJob->setData($job->getData());
            $newJob->setCreated($job->getCreated());
            $newJob->setChanged($job->getChanged());
            $newJob->setStatus($job->getStatus());
            $newJob->setStatusMessage($job->getStatusMessage());
            $newJob->setRetryAmount($job->getRetryAmount());
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
            throw new DigitalMarketingFrameworkException(sprintf('Foreign job object "%s" cannot be removed from this queue.', $job::class), 3702892151);
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
