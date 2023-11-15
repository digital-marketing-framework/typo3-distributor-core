<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller;

use Psr\Http\Message\ResponseInterface;

class DistributorStatisticsController extends AbstractDistributorController
{
    /**
     * @param array{minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string} $filters
     * @param array{sorting?:array<string,string>} $navigation
     */
    public function showErrorsAction(array $filters = [], array $navigation = []): ResponseInterface
    {
        $transformedFilters = $this->transformInputFilters($filters);
        $transformedNavigation = $this->transformInputNavigation($navigation, defaultSorting: ['count' => 'DESC', 'lastSeen' => 'DESC', 'firstSeen' => '']);

        $navigationBounds = [
            'sort' => ['count', 'firstSeen', 'lastSeen'],
            'sortDirection' => ['', 'ASC', 'DESC'],
        ];

        $this->view->assign('current', 'showErrors');
        $this->view->assign('filters', $filters);
        $this->view->assign('navigation', $transformedNavigation);
        $this->view->assign('navigationBounds', $navigationBounds);

        $errors = $this->queue->getErrorMessages($transformedFilters, $transformedNavigation);
        $this->view->assign('errors', $errors);

        return $this->backendHtmlResponse();
    }

    /**
     * @param array{minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string} $filters
     */
    public function showStatisticsAction(array $filters = []): ResponseInterface
    {
        $transformedFilters = $this->transformInputFilters($filters);
        $statistics = $this->queue->getStatistics($transformedFilters);

        $this->view->assign('current', 'showStatistics');
        $this->view->assign('filters', $filters);
        $this->view->assign('statistics', $statistics);

        return $this->backendHtmlResponse();
    }
}
