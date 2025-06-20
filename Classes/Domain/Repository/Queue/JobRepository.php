<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue;

use DateTime;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\GlobalConfiguration\GlobalConfigurationInterface;
use DigitalMarketingFramework\Core\Model\Queue\Error;
use DigitalMarketingFramework\Core\Model\Queue\Job;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\JobSchema;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Typo3\Core\Domain\Repository\ItemStorageRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Settings\QueueSettings;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * @extends ItemStorageRepository<JobInterface>
 */
class JobRepository extends ItemStorageRepository implements QueueInterface
{
    public function __construct(
        ConnectionPool $connectionPool,
    ) {
        parent::__construct($connectionPool, Job::class, 'tx_dmfdistributorcore_domain_model_queue_job');
    }

    public function getPid(): int
    {
        if ($this->pid === null) {
            if ($this->globalConfiguration instanceof GlobalConfigurationInterface) {
                $queueSettings = $this->globalConfiguration->getGlobalSettings(QueueSettings::class);
                $this->pid = $queueSettings->getPid();
            } else {
                $this->pid = 0;
            }
        }

        return $this->pid;
    }

    protected function mapDataField(string $name, mixed $value): mixed
    {
        switch ($name) {
            case 'created':
            case 'changed':
                if (!$value instanceof DateTime) {
                    $value = new DateTime('@' . $value);
                }

                return $value;
        }

        return parent::mapDataField($name, $value);
    }

    protected function mapItemField(string $name, mixed $value): mixed
    {
        switch ($name) {
            case 'created':
            case 'changed':
                if ($value instanceof DateTime) {
                    $value = $value->getTimestamp();
                }

                return $value;
        }

        return parent::mapItemField($name, $value);
    }

    // QUERY BUILDER PART START
    // the following query methods use the custom QueryBuilder so that they are able to execute COUNT(*) statements combined with GROUP BY statements
    // the result is a custom row and cannot be converted into an extbase model

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
     * @param array{minChanged?:?DateTime,maxChanged?:?DateTime,minCreated?:?DateTime,maxCreated?:?DateTime} $filters
     *
     * @return array<string>
     */
    protected function getTimeframeConditions(QueryBuilder $queryBuilder, array $filters): array
    {
        $minChanged = $filters['minChanged'] ?? null;
        $maxChanged = $filters['maxChanged'] ?? null;
        $minCreated = $filters['minCreated'] ?? null;
        $maxCreated = $filters['maxCreated'] ?? null;
        $conditions = [];

        if ($minChanged instanceof DateTime) {
            $conditions[] = $queryBuilder->expr()->gte('changed', $queryBuilder->createNamedParameter($filters['minChanged']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($maxChanged instanceof DateTime) {
            $conditions[] = $queryBuilder->expr()->lte('changed', $queryBuilder->createNamedParameter($filters['maxChanged']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($minCreated instanceof DateTime) {
            $conditions[] = $queryBuilder->expr()->gte('created', $queryBuilder->createNamedParameter($filters['minCreated']->getTimestamp(), Connection::PARAM_INT));
        }

        if ($maxCreated instanceof DateTime) {
            $conditions[] = $queryBuilder->expr()->lte('created', $queryBuilder->createNamedParameter($filters['maxCreated']->getTimestamp(), Connection::PARAM_INT));
        }

        return $conditions;
    }

    /**
     * @param array{searchFields?:array<string>,search?:string,advancedSearch?:bool} $filters
     *
     * @return array<string>
     */
    protected function getSearchConditions(QueryBuilder $queryBuilder, array $filters): array
    {
        // TODO searchExactMatch filter option removed
        $searchFields = $filters['searchFields'] ?? ['label', 'type', 'hash', 'status_message'];
        $search = $filters['search'] ?? '';
        $advancedSearch = $filters['advancedSearch'] ?? false;

        if ($search === '') {
            return [];
        }

        $search = $this->buildTextQueryString($search);

        if ($advancedSearch) {
            $searchFields[] = 'serialized_data';
        }

        $subConditions = [];
        foreach ($searchFields as $field) {
            $searchCondition = $queryBuilder->expr()->like($field, $queryBuilder->createNamedParameter($search, Connection::PARAM_STR));
            if ($field !== 'status_message') {
                $subConditions[] = $searchCondition;
            } else {
                // status message will only be searched for failed jobs
                $searchStatusConditions = [
                    $queryBuilder->expr()->eq('status', QueueInterface::STATUS_FAILED),
                    $searchCondition,
                ];
                $subConditions[] = $queryBuilder->expr()->and(...$searchStatusConditions);
            }
        }

        return [
            $queryBuilder->expr()->or(...$subConditions),
        ];
    }

    /**
     * @param array{type?:array<string>} $filters
     *
     * @return array<string>
     */
    protected function getTypeConditions(QueryBuilder $queryBuilder, array $filters): array
    {
        $type = $filters['type'] ?? [];

        if ($type === []) {
            return [];
        }

        return [
            $queryBuilder->expr()->in('type', $queryBuilder->createNamedParameter($type, Connection::PARAM_STR_ARRAY)),
        ];
    }

    /**
     * @param array{status?:array<int>,skipped?:?bool} $filters
     *
     * @return array<string>
     */
    protected function getStatusConditions(QueryBuilder $queryBuilder, array $filters): array
    {
        $status = $filters['status'] ?? [];
        $skipped = $filters['skipped'] ?? null;

        $conditions = [];

        if ($status !== []) {
            $conditions[] = $queryBuilder->expr()->in('status', $queryBuilder->createNamedParameter($status, Connection::PARAM_INT_ARRAY));
        }

        if ($skipped !== null) {
            $skippedFinishedConditions = [
                $queryBuilder->expr()->neq('status', $queryBuilder->createNamedParameter(QueueInterface::STATUS_DONE, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('skipped', $queryBuilder->createNamedParameter($skipped ? 1 : 0, Connection::PARAM_INT)),
            ];
            $conditions[] = $queryBuilder->expr()->or(...$skippedFinishedConditions);
        }

        return $conditions;
    }

    /**
     * @param array{uid?:int|array<int>} $filters
     *
     * @return array<string>
     */
    protected function getIdConditions(QueryBuilder $queryBuilder, array $filters): array
    {
        $ids = $filters['uid'] ?? [];

        if ($ids === []) {
            return [];
        }

        return [
            $this->getFilterCondition($queryBuilder, 'uid', $ids),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     */
    protected function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        $conditions = [
            ...$this->getIdConditions($queryBuilder, $filters),
            ...$this->getSearchConditions($queryBuilder, $filters),
            ...$this->getTimeframeConditions($queryBuilder, $filters),
            ...$this->getTypeConditions($queryBuilder, $filters),
            ...$this->getStatusConditions($queryBuilder, $filters),
        ];

        if ($conditions !== []) {
            $queryBuilder->andWhere(...$conditions);
        }
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
        $conditions = $this->getTimeframeConditions($query, $filters);
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
        $conditions = $this->getTimeframeConditions($query, $filters);
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
    public function fetchJobTypes(): array
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
        $conditions = $this->getTimeframeConditions($query, $filters);
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
            $lastSeen = $this->fetchOneByErrorMessage($message, lastSeen: true, filters: $filters);
            if (!$lastSeen instanceof Job) {
                throw new DigitalMarketingFrameworkException('cannot load job "lastSeen" for error statistics', 8748465510);
            }

            $result[$message]['lastSeen'] = $lastSeen;

            $firstSeen = $this->fetchOneByErrorMessage($message, lastSeen: false, filters: $filters);
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
    public function fetchOneByErrorMessage(string $message, bool $lastSeen, array $filters): ?JobInterface
    {
        $filters = [
            'search' => $message,
            'advancedSearch' => false,
            'status' => [QueueInterface::STATUS_FAILED],
            'searchFields' => ['status_message'],
        ];

        $navigation = [
            'sorting' => [
                'changed' => $lastSeen ? 'DESC' : 'ASC',
            ],
        ];

        return $this->fetchOneFiltered($filters, $navigation);
    }

    /**
     * @param array<int> $status
     *
     * @return array<JobInterface>
     */
    protected function fetchWhere(array $status = [], int $limit = 0, int $offset = 0, int $minTimeSinceChangedInSeconds = 0, int $minAgeInSeconds = 0): array
    {
        $maxChanged = null;
        if ($minTimeSinceChangedInSeconds > 0) {
            $maxChanged = new DateTime();
            $maxChanged->modify('- ' . $minTimeSinceChangedInSeconds . ' seconds');
        }

        $maxCreated = null;
        if ($minAgeInSeconds > 0) {
            $maxCreated = new DateTime();
            $maxCreated->modify('- ' . $minAgeInSeconds . ' seconds');
        }

        $filters = [
            'status' => $status,
            'maxChanged' => $maxChanged,
            'maxCreated' => $maxCreated,
        ];

        $navigation = [
            'limit' => $limit,
            'offset' => $offset,
            'sorting' => [
                'created' => 'ASC',
                'uid' => 'ASC',
            ],
        ];

        return $this->fetchFiltered($filters, $navigation);
    }

    public function fetchByStatus(array $status = [], int $limit = 0, int $offset = 0): array
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
        $job->setStatus($status);
        $job->setSkipped($skipped);

        if ($message !== null) {
            $job->addStatusMessage($message);
        }

        if (!$preserveTimestamp) {
            $job->setChanged(new DateTime());
        }

        $this->update($job);
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

    public function removeOldJobs(int $minAgeInSeconds, array $status = []): void
    {
        $jobs = $this->fetchWhere($status, 0, 0, 0, $minAgeInSeconds);
        foreach ($jobs as $job) {
            $this->remove($job);
        }
    }

    public static function getSchema(): ContainerSchema
    {
        return new JobSchema();
    }
}
