<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\UriRouteResolver;

use DigitalMarketingFramework\Typo3\Core\Backend\UriRouteResolver\Typo3EditRecordUriRouteResolver;

class DistributorEditUriRouteResolver extends Typo3EditRecordUriRouteResolver
{
    protected function getRouteMatch(): string
    {
        return 'page.distributor.edit';
    }

    protected function getTableName(): string
    {
        return 'tx_dmfdistributorcore_domain_model_queue_job';
    }
}
