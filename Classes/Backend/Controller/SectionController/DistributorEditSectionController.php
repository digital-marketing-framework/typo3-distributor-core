<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\Controller\SectionController;

use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Backend\Controller\SectionController\EditSectionController;

class DistributorEditSectionController extends EditSectionController
{
    public const WEIGHT = 0;

    public function __construct(string $keyword, RegistryInterface $registry)
    {
        parent::__construct($keyword, $registry, 'distributor');
    }

    protected function getTableName(): string
    {
        return 'tx_dmfdistributorcore_domain_model_queue_job';
    }
}
