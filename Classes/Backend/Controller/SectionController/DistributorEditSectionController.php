<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\Controller\SectionController;

use DigitalMarketingFramework\Core\Backend\Controller\SectionController\SectionController;
use DigitalMarketingFramework\Core\Backend\Response\RedirectResponse;
use DigitalMarketingFramework\Core\Backend\Response\Response;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DistributorEditSectionController extends SectionController
{
    public const WEIGHT = 0;

    public function __construct(string $keyword, RegistryInterface $registry)
    {
        parent::__construct($keyword, $registry, 'distributor', ['edit']);
    }

    protected function editAction(): Response
    {
        $id = $this->getParameters()['id'] ?? '';
        $returnUrl = $this->getReturnUrl();

        $typo3UriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $parameters = [
            'edit' => ['tx_dmfdistributorcore_domain_model_queue_job' => [$id => 'edit']],
            'returnUrl' => $returnUrl,
        ];
        $url = $typo3UriBuilder->buildUriFromRoute('record_edit', $parameters);

        return new RedirectResponse($url);
    }
}
