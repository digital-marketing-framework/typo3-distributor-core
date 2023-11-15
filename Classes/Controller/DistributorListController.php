<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller;

use DateTime;
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
     * @param array{minCreated:int,maxCreated:int,minChanged:int,maxChanged:int,type:array<string>,status:array<int>,skipped:?bool} $filters
     * @param array{page?:int,itemsPerPage?:int,sorting?:array<string,string>} $navigation
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
     * @param array{page?:int,itemsPerPage?:int,sorting?:array<string,string>} $navigation
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
     * @param array{page?:int,itemsPerPage?:int,sorting?:array<string,string>} $navigation
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
     * @param array{page?:int,itemsPerPage?:int,sorting?:array<string,string>} $navigation
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
     * @param array{page?:int,itemsPerPage?:int,sorting?:array<string,string>} $navigation
     */
    public function listAction(string $currentAction = 'list', ?int $page = null, array $filters = [], array $navigation = []): ResponseInterface
    {
        $this->view->assign('current', $currentAction);
        if ($page !== null) {
            $navigation['page'] = $page;
        }

        $this->view->assign('expirationDate', $this->getExpirationDate());
        $this->view->assign('stuckDate', $this->getStuckDate());

        $transformedFilters = $this->transformInputFilters($filters);

        $navigation['page'] ??= 0;
        $navigation['itemsPerPage'] ??= 20;
        $navigation['sorting'] ??= ['changed' => 'DESC', 'created' => '', 'type' => '', 'status' => ''];

        $filterBounds = $this->getFilterBounds($transformedFilters);
        $navigationBounds = $this->getNavigationBounds($transformedFilters, $navigation);
        if ($navigation['page'] >= $navigationBounds['numberOfPages']) {
            $navigation['page'] = $navigationBounds['numberOfPages'] - 1;
        }

        $this->view->assign('filters', $filters);
        $this->view->assign('navigation', $navigation);

        $this->view->assign('filterBounds', $filterBounds);
        $this->view->assign('navigationBounds', $navigationBounds);

        $jobs = $this->queue->fetchFiltered($transformedFilters, $navigation);
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
