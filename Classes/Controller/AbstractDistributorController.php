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
            'type' => is_array($filters['type']) ? array_keys(array_filter($filters['type'])) : [],
        ];

        $result['status'] = [];
        $result['skipped'] = null;

        $inputStatus = is_array($filters['status']) ? array_keys(array_filter($filters['status'])) : [];
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
     * @param array{minCreated:int,maxCreated:int,minChanged:int,maxChanged:int,type:array<string>,status:array<int>,skipped:?bool} $filters
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
     * @param array{minCreated:int,maxCreated:int,minChanged:int,maxChanged:int,type:array<string>,status:array<int>,skipped:?bool} $filters
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
     * @param array{minCreated:int,maxCreated:int,minChanged:int,maxChanged:int,type:array<string>,status:array<int>,skipped:?bool} $filters
     *
     * @return array{type:array<string,int>,status:array<string,int>,typeCountNotEmpty:int,typeSelected:bool,statusCountNotEmpty:int,statusSelected:bool}
     */
    protected function getFilterBounds(array $filters): array
    {
        $types = $this->getTypeFilterBounds($filters);
        $status = $this->getStatusFilterBounds($filters);

        $typeCountNotEmpty = count(array_filter($types, function (int $count) {
            return $count > 0;
        }));
        $typeSelected = $filters['type'] !== [];

        $statusCountNotEmpty = count(array_filter($status, function (int $count) {
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
}
