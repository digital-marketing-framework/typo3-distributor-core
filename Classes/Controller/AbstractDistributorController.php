<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Controller;

use DateTime;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Typo3\Core\Controller\AbstractBackendController;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Repository\Queue\JobRepository;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;

abstract class AbstractDistributorController extends AbstractBackendController
{
    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        protected JobRepository $queue,
    ) {
        parent::__construct($moduleTemplateFactory, $iconFactory);
    }

    /**
     * @param array{search?:string,advancedSearch?:bool,searchExactMatch?:bool,minCreated?:string,maxCreated?:string,minChanged?:string,maxChanged?:string,type?:array<string,string>,status?:array<string>} $filters
     *
     * @return array{search:string,advancedSearch:bool,searchExactMatch:bool,minCreated:?DateTime,maxCreated:?DateTime,minChanged:?DateTime,maxChanged:?DateTime,type:array<string>,status:array<int>,skipped:?bool}
     */
    protected function transformInputFilters(array $filters): array
    {
        $result = [
            'search' => $filters['search'] ?? '',
            'advancedSearch' => $filters['advancedSearch'] ?? false,
            'searchExactMatch' => $filters['searchExactMatch'] ?? false,
            'minCreated' => isset($filters['minCreated']) && $filters['minCreated'] !== '' ? new DateTime($filters['minCreated']) : null,
            'maxCreated' => isset($filters['maxCreated']) && $filters['maxCreated'] !== '' ? new DateTime($filters['maxCreated']) : null,
            'minChanged' => isset($filters['minChanged']) && $filters['minChanged'] !== '' ? new DateTime($filters['minChanged']) : null,
            'maxChanged' => isset($filters['maxChanged']) && $filters['maxChanged'] !== '' ? new DateTime($filters['maxChanged']) : null,
            'type' => isset($filters['type']) ? array_keys(array_filter($filters['type'])) : [],
        ];

        $result['status'] = [];
        $result['skipped'] = null;

        $inputStatus = isset($filters['status']) ? array_keys(array_filter($filters['status'])) : [];
        $skippedFound = false;
        $notSkippedFound = false;
        foreach ($inputStatus as $status) {
            switch ($status) {
                case 'queued':
                    $result['status'][] = QueueInterface::STATUS_QUEUED;
                    break;
                case 'pending':
                    $result['status'][] = QueueInterface::STATUS_PENDING;
                    break;
                case 'running':
                    $result['status'][] = QueueInterface::STATUS_RUNNING;
                    break;
                case 'doneNotSkipped':
                    $result['status'][] = QueueInterface::STATUS_DONE;
                    $notSkippedFound = true;
                    break;
                case 'doneSkipped':
                    $result['status'][] = QueueInterface::STATUS_DONE;
                    $skippedFound = true;
                    break;
                case 'failed':
                    $result['status'][] = QueueInterface::STATUS_FAILED;
                    break;
            }
        }

        if (!$skippedFound && $notSkippedFound) {
            $result['skipped'] = false;
        } elseif ($skippedFound && !$notSkippedFound) {
            $result['skipped'] = true;
        }

        return $result;
    }

    /**
     * @param array{page?:int|string,itemsPerPage?:int|string,sorting?:array<string,string>} $navigation
     * @param array<string,string> $defaultSorting
     *
     * @return array{page:int,itemsPerPage:int,sorting:array<string,string>}
     */
    protected function transformInputNavigation(array $navigation, array $defaultSorting): array
    {
        return [
            'page' => (int)($navigation['page'] ?? 0),
            'itemsPerPage' => (int)($navigation['itemsPerPage'] ?? 20),
            'sorting' => $navigation['sorting'] ?? $defaultSorting,
        ];
    }
}
