<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

use DigitalMarketingFramework\Distributor\Core\DataSource\DistributorDataSourceStorage;
use DigitalMarketingFramework\Distributor\Core\Model\DataSource\DistributorDataSourceInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource\Typo3FormDataSource;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;

/**
 * @extends DistributorDataSourceStorage<Typo3FormDataSource>
 */
class Typo3FormDataSourceStorage extends DistributorDataSourceStorage
{
    public function __construct(
        string $keyword,
        Registry $registry,
        protected Typo3FormService $formService,
    ) {
        parent::__construct($keyword, $registry);
    }

    public function getType(): string
    {
        return Typo3FormDataSource::TYPE;
    }

    /**
     * @param array<string,mixed> $dataSourceContext
     */
    public function getDataSourceById(string $id, array $dataSourceContext): ?DistributorDataSourceInterface
    {
        $formId = $this->getInnerIdentifier($id);
        $formDefinition = $this->formService->getFormById($formId, $dataSourceContext);

        if ($formDefinition !== null) {
            return new Typo3FormDataSource($formId, $formDefinition);
        }

        return null;
    }

    public function getAllDataSources(): array
    {
        // TODO is there a better way to fetch the request from here?
        $dataSourceContext = $this->formService->getFormDataSourceContext($GLOBALS['TYPO3_REQUEST'] ?? null);

        $result = [];
        $forms = $this->formService->getAllForms($dataSourceContext);
        foreach ($forms as $id => $formDefinition) {
            $result[] = new Typo3FormDataSource($this->getOuterIdentifier($id), $formDefinition);
        }

        return $result;
    }
}
