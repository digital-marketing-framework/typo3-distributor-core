<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller;

use DateTime;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;

class DistributorListController extends AbstractDistributorController
{
    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        JobRepository $queue,
        protected Registry $registry,
    ) {
        parent::__construct($moduleTemplateFactory, $iconFactory, $queue);
    }

    protected function getExpirationDate(): DateTime
    {
        $expirationTime = $this->registry->getGlobalConfiguration()->get('dmf_distributor_core')['queue']['expirationTime'] ?? 30;
        $expirationDate = new DateTime();
        $expirationDate->modify('-' . $expirationTime . ' days');

        return $expirationDate;
    }

    protected function getStuckDate(): DateTime
    {
        $stuckDate = new DateTime();
        $stuckDate->modify('-3600 seconds');

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
                $this->registry->getRelay()
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

        $this->view->assign('current', $currentAction);
        $this->view->assign('expirationDate', $this->getExpirationDate());
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
}
