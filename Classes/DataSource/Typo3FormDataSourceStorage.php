<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

use DigitalMarketingFramework\Core\DataSource\DataSourceStorage;
use DigitalMarketingFramework\Core\Model\DataSource\DataSourceInterface;
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
        $forms = $this->formPersistenceManager->listForms();
        $result = [];

        foreach ($forms as $form) {
            $formDefinition = $this->formPersistenceManager->load($form['persistenceIdentifier']);
            $result[] = new Typo3FormDataSource(
                $this->getOuterIdentifier($form['persistenceIdentifier']),
                $formDefinition
            );
        }
    }
}
