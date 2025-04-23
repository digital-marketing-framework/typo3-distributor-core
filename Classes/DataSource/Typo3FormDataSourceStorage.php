<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

use DigitalMarketingFramework\Core\DataSource\DataSourceStorage;
use DigitalMarketingFramework\Core\Model\DataSource\DataSourceInterface;
use DigitalMarketingFramework\Distributor\Core\DataSource\DistributorDataSourceStorage;
use DigitalMarketingFramework\Distributor\Core\Model\DataSource\DistributorDataSourceInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource\Typo3FormDataSource;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * @extends DistributorDataSourceStorage<Typo3FormDataSource>
 */
class Typo3FormDataSourceStorage extends DistributorDataSourceStorage
{
    public function __construct(
        string $keyword,
        Registry $registry,
        protected FormPersistenceManagerInterface $formPersistenceManager,
    ) {
        parent::__construct($keyword, $registry);
    }

    public function getType(): string
    {
        return Typo3FormDataSource::TYPE;
    }

    public function getDataSourceById(string $id): ?DistributorDataSourceInterface
    {
        $formId = $this->getInnerIdentifier($id);
        if ($this->formPersistenceManager->exists($formId)) {
            $formDefinition = $this->formPersistenceManager->load($formId);

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
