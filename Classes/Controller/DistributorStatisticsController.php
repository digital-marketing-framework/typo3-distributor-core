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
        $this->view->assign('current', 'showErrors');
        $transformedFilters = $this->transformInputFilters($filters);
        $this->view->assign('filters', $filters);

        $navigation['sorting'] ??= ['count' => 'DESC', 'lastSeen' => 'DESC', 'firstSeen' => ''];
        $this->view->assign('navigation', $navigation);

        $navigationBounds = [
            'sort' => ['count', 'firstSeen', 'lastSeen'],
            'sortDirection' => ['', 'ASC', 'DESC'],
        ];
        $this->view->assign('navigationBounds', $navigationBounds);

        $errors = $this->queue->getErrorMessages($transformedFilters, $navigation);

        $this->view->assign('errors', $errors);

        return $this->backendHtmlResponse();
    }

    /**
     * @param array{minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string} $filters
     */
    public function showStatisticsAction(array $filters = []): ResponseInterface
    {
        $this->view->assign('current', 'showStatistics');
        $transformedFilters = $this->transformInputFilters($filters);
        $this->view->assign('filters', $filters);
        $statistics = $this->queue->getStatistics($transformedFilters);
        $this->view->assign('statistics', $statistics);

        return $this->backendHtmlResponse();
    }
}
