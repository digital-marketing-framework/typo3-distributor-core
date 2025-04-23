<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller;

use DateTime;
use DigitalMarketingFramework\Core\Model\DataSource\DataSourceInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\TestCase\DistributorTestCaseProcessor;
use DigitalMarketingFramework\Typo3\Core\Domain\Model\TestCase\TestCase;
use DigitalMarketingFramework\Typo3\Core\Domain\Repository\TestCase\TestCaseRepository;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue\Job;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\Queue\GlobalConfiguration\Settings\QueueSettings;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class DistributorListController extends AbstractDistributorController
{
    /**
     * @var int
     */
    protected const PAGINATION_ITEMS_EACH_SIDE = 3;

    protected RegistryInterface $registry;

    protected QueueSettings $queueSettings;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        JobRepository $queue,
        RegistryCollection $registryCollection,
        protected TestCaseRepository $testCaseRepository,
        protected PersistenceManager $persistenceManager,
    ) {
        $this->registry = $registryCollection->getRegistryByClass(RegistryInterface::class);
        $this->queueSettings = $this->registry->getGlobalConfiguration()->getGlobalSettings(QueueSettings::class);
        parent::__construct($moduleTemplateFactory, $iconFactory, $queue);
    }

    protected function getExpirationDate(): DateTime
    {
        $expirationTime = $this->queueSettings->getExpirationTime();
        $expirationDate = new DateTime();
        $expirationDate->modify('-' . $expirationTime . ' days');

        return $expirationDate;
    }

    protected function getStuckDate(): DateTime
    {
        $maxExecutionTime = $this->queueSettings->getMaximumExecutionTime();
        $stuckDate = new DateTime();
        $stuckDate->modify('-' . $maxExecutionTime . ' seconds');

        return $stuckDate;
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     *
     * @return array<string,int>
     */
    protected function getTypeFilterBounds(array $filters): array
    {
        $types = [];
        $allTypes = $this->queue->getJobTypes();
        $allTypes = array_merge($allTypes, $filters['type']);
        foreach ($allTypes as $type) {
            $typeFilters = $filters;
            $typeFilters['type'] = [$type];
            $count = $this->queue->countFiltered($typeFilters);
            $types[$type] = $count;
        }

        return $types;
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     *
     * @return array<string,int>
     */
    protected function getStatusFilterBounds(array $filters): array
    {
        $statusValues = [];
        foreach (['queued', 'pending', 'running', 'doneNotSkipped', 'doneSkipped', 'failed'] as $status) {
            $statusFilters = $filters;
            switch ($status) {
                case 'queued':
                    $statusFilters['status'] = [QueueInterface::STATUS_QUEUED];
                    $statusFilters['skipped'] = null;
                    break;
                case 'pending':
                    $statusFilters['status'] = [QueueInterface::STATUS_PENDING];
                    $statusFilters['skipped'] = null;
                    break;
                case 'running':
                    $statusFilters['status'] = [QueueInterface::STATUS_RUNNING];
                    $statusFilters['skipped'] = null;
                    break;
                case 'doneNotSkipped':
                    $statusFilters['status'] = [QueueInterface::STATUS_DONE];
                    $statusFilters['skipped'] = false;
                    break;
                case 'doneSkipped':
                    $statusFilters['status'] = [QueueInterface::STATUS_DONE];
                    $statusFilters['skipped'] = true;
                    break;
                case 'failed':
                    $statusFilters['status'] = [QueueInterface::STATUS_FAILED];
                    $statusFilters['skipped'] = null;
                    break;
            }

            $count = $this->queue->countFiltered($statusFilters);
            $statusValues[$status] = $count;
        }

        return $statusValues;
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     *
     * @return array{type:array<string,int>,status:array<string,int>,typeCountNotEmpty:int,typeSelected:bool,statusCountNotEmpty:int,statusSelected:bool}
     */
    protected function getFilterBounds(array $filters): array
    {
        $types = $this->getTypeFilterBounds($filters);
        $status = $this->getStatusFilterBounds($filters);

        $typeCountNotEmpty = count(array_filter($types, static function (int $count) {
            return $count > 0;
        }));
        $typeSelected = $filters['type'] !== [];

        $statusCountNotEmpty = count(array_filter($status, static function (int $count) {
            return $count > 0;
        }));
        $statusSelected = $filters['status'] !== [];

        return [
            'type' => $types,
            'typeCountNotEmpty' => $typeCountNotEmpty,
            'typeSelected' => $typeSelected,
            'status' => $status,
            'statusCountNotEmpty' => $statusCountNotEmpty,
            'statusSelected' => $statusSelected,
        ];
    }

    /**
     * @param array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool} $filters
     * @param array{page:int,itemsPerPage:int,sorting:array<string,string>} $navigation
     *
     * @return array{numberOfPages:int,pages:array<int>,sort:array<string>,sortDirection:array<string>}
     */
    protected function getNavigationBounds(array $filters, array $navigation): array
    {
        $numberOfPages = 1;
        $count = $this->queue->countFiltered($filters);
        if ($navigation['itemsPerPage'] > 0 && $count > $navigation['itemsPerPage']) {
            $numberOfPages = ceil($count / $navigation['itemsPerPage']);
        }

        return [
            'numberOfPages' => $numberOfPages,
            'numberOfItems' => $count,
            'pages' => array_keys(array_fill(0, $numberOfPages, 1)),
            'sort' => ['changed', 'created', 'type', 'status'],
            'sortDirection' => ['', 'ASC', 'DESC'],
        ];
    }

    /**
     * @param array<string,string> $list
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     */
    public function deleteAction(array $list = [], array $filters = [], array $navigation = []): ResponseInterface
    {
        $uidList = array_values(array_filter($list));
        if ($uidList !== []) {
            $jobs = $this->queue->findByUidList($uidList);
            foreach ($jobs as $job) {
                $this->queue->removeJob($job);
            }
        }

        return $this->redirectResponse(action: 'list', arguments: [
            'list' => $list,
            'filters' => $filters,
            'navigation' => $navigation,
        ]);
    }

    /**
     * @param array<string,string> $list
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     */
    public function runAction(array $list = [], array $filters = [], array $navigation = []): ResponseInterface
    {
        $uidList = array_values(array_filter($list));
        if ($uidList !== []) {
            $jobs = $this->queue->findByUidList($uidList);
            $worker = $this->registry->getQueueProcessor(
                $this->queue,
                $this->registry->getDistributor()
            );
            $worker->processJobs($jobs);
        }

        return $this->redirectResponse(action: 'list', arguments: [
            'list' => $list,
            'filters' => $filters,
            'navigation' => $navigation,
        ]);
    }

    /**
     * @param array<string,string> $list
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     */
    public function previewAction(array $list = [], array $filters = [], array $navigation = []): ResponseInterface
    {
        $records = [];
        $uidList = array_values(array_filter($list));
        if ($uidList !== []) {
            $jobs = $this->queue->findByUidList($uidList);
            $distributor = $this->registry->getDistributor();
            foreach ($jobs as $job) {
                $records[] = [
                    'job' => $job,
                    'preview' => $distributor->previewJobProcess($job),
                ];
            }
        }

        $testCases = $this->testCaseRepository->getTypeSpecificTestCases(DistributorTestCaseProcessor::TEST_CASE_TYPE);
        $distributorTestCases = [];
        $testCaseMap = [];
        if ($testCases !== []) {
            $distributorTestCases[''] = '';
            foreach ($testCases as $testCase) {
                $testCaseMap[$testCase->getName()] = $testCase->getUid();
                $distributorTestCases[$testCase->getUid()] = $testCase->getLabel() !== '' && $testCase->getLabel() !== $testCase->getName()
                    ? sprintf('%s (%s)', $testCase->getLabel(), $testCase->getName())
                    : $testCase->getName();
            }
        }
        $this->view->assign('testCases', $distributorTestCases);
        $this->view->assign('testCaseMap', $testCaseMap);

        $this->view->assign('records', $records);

        $this->view->assign('backParameters', [
            'list' => $list,
            'filters' => $filters,
            'navigation' => $navigation,
        ]);

        return $this->backendHtmlResponse();
    }

    protected function getTestCaseData(int $jobUid): array
    {
        $job = $this->queue->findByUid($jobUid);
        $jobData = $job->getData();

        $distributor = $this->registry->getDistributor();
        $jobOutput = $distributor->getJobPreviewData($job);

        $hash = '';
        $dataSourceId = $jobData['submission']['dataSourceId'] ?? '';
        if ($dataSourceId !== '') {
            $dataSource = $this->registry->getDistributorDataSourceManager()->getDataSourceById($dataSourceId);
            if ($dataSource instanceof DataSourceInterface) {
                $hash = $dataSource->getHash();
            }
        }

        return [
            'name' => $job->getLabel(),
            'input' => $jobData,
            'expectedOutput' => $jobOutput,
            'hash' => $hash,
        ];
    }

    public function createTestCaseAction(int $jobUid): ResponseInterface
    {
        $testCaseData = $this->getTestCaseData($jobUid);

        $testCase = null;
        $testCases = $this->testCaseRepository->findByName($testCaseData['name']);
        foreach ($testCases as $case) {
            if ($job->getType() === DistributorTestCaseProcessor::TEST_CASE_TYPE) {
                $testCase = $case;
                break;
            }
        }
        if (!$testCase instanceof TestCase) {
            $testCase = new TestCase(
                $testCaseData['name'],
                $testCaseData['name'],
                DistributorTestCaseProcessor::TEST_CASE_TYPE,
                $testCaseData['hash']
            );
            $testCase->setPid($this->testCaseRepository->getPid());
            $testCase->setInput($testCaseData['input']);
            $testCase->setExpectedOutput($testCaseData['expectedOutput']);
            $this->testCaseRepository->add($testCase);
            $this->persistenceManager->persistAll();
        } else {
            if ($testCase->getLabel() === '') {
                $testCase->setLabel($testCaseData['name']);
            }
            $testCase->setHash($testCaseData['hash']);
            $testCase->setInput($testCaseData['input']);
            $testCase->setExpectedOutput($testCaseData['expectedOutput']);
            $this->testCaseRepository->update($testCase);
        }

        $params = [
            'edit' => ['tx_dmfcore_domain_model_test_case' => [$testCase->getUid() => 'edit']],
            // 'returnUrl' => '', // TODO
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);

        return new RedirectResponse($uri);
    }

    public function updateTestCaseAction(int $jobUid, int $testCaseUid): ResponseInterface
    {
        $testCaseData = $this->getTestCaseData($jobUid);

        $testCase = $this->testCaseRepository->findByUid($testCaseUid);
        if ($testCase->getLabel() === '') {
            $testCase->setLabel($testCaseData['name']);
        }
        $testCase->setInput($testCaseData['input']);
        $testCase->setExpectedOutput($testCaseData['expectedOutput']);
        $testCase->setHash($testCaseData['hash']);
        $this->testCaseRepository->update($testCase);

        $params = [
            'edit' => ['tx_dmfcore_domain_model_test_case' => [$testCase->getUid() => 'edit']],
            // 'returnUrl' => '', // TODO
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);

        return new RedirectResponse($uri);
    }

    /**
     * @param array<string,string> $list
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     */
    public function queueAction(array $list = [], array $filters = [], array $navigation = []): ResponseInterface
    {
        $uidList = array_values(array_filter($list));
        if ($uidList !== []) {
            $jobs = $this->queue->findByUidList($uidList);
            $this->queue->markListAsQueued($jobs);
        }

        return $this->redirectResponse(action: 'list', arguments: [
            'list' => $list,
            'filters' => $filters,
            'navigation' => $navigation,
        ]);
    }

    /**
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     */
    public function listAction(string $currentAction = 'list', ?int $page = null, array $filters = [], array $navigation = []): ResponseInterface
    {
        $transformedFilters = $this->transformInputFilters($filters);
        $transformedNavigation = $this->transformInputNavigation($navigation, defaultSorting: ['changed' => 'DESC', 'created' => '', 'type' => '', 'status' => '']);
        $filterBounds = $this->getFilterBounds($transformedFilters);
        $navigationBounds = $this->getNavigationBounds($transformedFilters, $transformedNavigation);

        if ($page !== null) {
            $transformedNavigation['page'] = $page;
        }

        if ($transformedNavigation['page'] >= $navigationBounds['numberOfPages']) {
            $transformedNavigation['page'] = $navigationBounds['numberOfPages'] - 1;
        }

        $navigationBounds['pages'] = $this->getPagesForPagination($navigationBounds['pages'], $transformedNavigation['page'], $navigationBounds['numberOfPages']);

        $this->view->assign('current', $currentAction);
        $this->view->assign('expirationDate', $this->getExpirationDate());
        $this->view->assign('maxExecutionTime', $this->queueSettings->getMaximumExecutionTime());
        $this->view->assign('stuckDate', $this->getStuckDate());

        $this->view->assign('filters', $filters);
        $this->view->assign('navigation', $transformedNavigation);

        $this->view->assign('filterBounds', $filterBounds);
        $this->view->assign('navigationBounds', $navigationBounds);

        $jobs = $this->queue->fetchFiltered($transformedFilters, $transformedNavigation);
        $this->view->assign('jobs', $jobs);

        return $this->backendHtmlResponse();
    }

    public function listExpiredAction(): ResponseInterface
    {
        $maxChanged = $this->getExpirationDate();

        return $this->redirectResponse(action: 'list', arguments: [
            'currentAction' => 'listExpired',
            'filters' => [
                'maxChanged' => $maxChanged->format('Y-m-d\\TH:i'),
                'status' => ['doneNotSkipped' => '1', 'doneSkipped' => '1'],
            ],
        ]);
    }

    public function listStuckAction(): ResponseInterface
    {
        $maxChanged = $this->getStuckDate();

        return $this->redirectResponse(action: 'list', arguments: [
            'currentAction' => 'listStuck',
            'filters' => [
                'maxChanged' => $maxChanged->format('Y-m-d\\TH:i'),
                'status' => ['queued' => 1, 'pending' => 1, 'running' => 1],
            ],
        ]);
    }

    public function listFailedAction(): ResponseInterface
    {
        return $this->redirectResponse(action: 'list', arguments: [
            'currentAction' => 'listFailed',
            'filters' => [
                'status' => ['failed' => 1],
            ],
        ]);
    }

    /**
     * @param array<int> $pages
     *
     * @return array<string|int>
     */
    protected function getPagesForPagination(array $pages, int $currentPage, int $totalPages): array
    {
        // Limit Pagination page links
        if ($totalPages > (4 * static::PAGINATION_ITEMS_EACH_SIDE + 3)) {
            $pages = [];
            $startPage = $currentPage - static::PAGINATION_ITEMS_EACH_SIDE;
            $endPage = $currentPage + static::PAGINATION_ITEMS_EACH_SIDE;

            if ($currentPage <= 2 * static::PAGINATION_ITEMS_EACH_SIDE + 1) {
                // Current page close to beginning
                $startPage = 0;
                $endPage = (3 * static::PAGINATION_ITEMS_EACH_SIDE) + 1;
            } elseif ($currentPage >= $totalPages - (2 * static::PAGINATION_ITEMS_EACH_SIDE + 2)) {
                // Current page close to end
                $startPage = $totalPages - (3 * static::PAGINATION_ITEMS_EACH_SIDE) - 2;
                $endPage = $totalPages - 1;
            }

            if ($startPage > 0) {
                $pages = array_keys(array_fill(0, static::PAGINATION_ITEMS_EACH_SIDE, 1));
            }

            if ($startPage > 1) {
                $pages[] = '...';
            }

            $pages = [...$pages, ...array_keys(array_fill($startPage, $endPage - $startPage + 1, 1))];
            if ($endPage < $totalPages - static::PAGINATION_ITEMS_EACH_SIDE) {
                $pages[] = '...';
            }

            if ($endPage < $totalPages - 1) {
                $pages = [...$pages, ...array_keys(array_fill($totalPages - static::PAGINATION_ITEMS_EACH_SIDE, static::PAGINATION_ITEMS_EACH_SIDE, 1))];
            }
        }

        return $pages;
    }
}
