<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataSource;

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

    public function getDataSourceByIdentifier(string $identifier): ?DistributorDataSourceInterface
    {
        if (!$this->matches($identifier)) {
            return null;
        }

        $formId = $this->getInnerIdentifier($identifier);
        $formDefinition = $this->formService->getFormById($formId);
        if ($formDefinition === null) {
            return null;
        }

        return new Typo3FormDataSource($formId, $formDefinition);
    }

    public function getAllDataSources(): array
    {
        $result = [];
        $forms = $this->formService->getAllForms();
        foreach ($forms as $id => $formDefinition) {
            $result[] = new Typo3FormDataSource($id, $formDefinition);
        }

        return $result;
    }

    public function getAllDataSourceVariants(): array
    {
        $result = [];
        $forms = $this->formService->getAllForms();
        foreach ($forms as $id => $formDefinition) {
            $result[] = new Typo3FormDataSource($id, $formDefinition);
        }

        foreach ($this->formService->getAllFormPluginVariants() as $variant) {
            $result[] = new Typo3FormDataSource(
                $variant['formId'],
                $variant['formDefinition'],
                $variant['dataSourceContext'],
                $variant['overrideDocument'],
                applyOverride: true,
            );
        }

        return $result;
    }

    public function getAllDataSourceVariantIdentifiers(): array
    {
        $identifiers = [];

        // Base form identifiers
        $forms = $this->formService->getAllForms();
        foreach (array_keys($forms) as $id) {
            $identifiers[] = $this->getOuterIdentifier($id);
        }

        // Form plugin variant identifiers
        foreach ($this->formService->getAllFormPluginVariants() as $variant) {
            $identifiers[] = $this->getOuterIdentifier($variant['formId'] . ':' . $variant['dataSourceContext']['pluginId']);
        }

        return $identifiers;
    }

    public function getDataSourceVariantByIdentifier(string $identifier, bool $maintenanceMode = false): ?DataSourceInterface
    {
        if (!$this->matches($identifier)) {
            return null;
        }

        $innerIdentifier = $this->getInnerIdentifier($identifier);

        // Plugin IDs are numeric content element UIDs appended after the last colon.
        // Form IDs can contain colons (e.g. EXT:...), so split from the right.
        $lastColon = strrpos($innerIdentifier, ':');
        if ($lastColon !== false && is_numeric(substr($innerIdentifier, $lastColon + 1))) {
            $formId = substr($innerIdentifier, 0, $lastColon);
            $pluginId = substr($innerIdentifier, $lastColon + 1);
        } else {
            $formId = $innerIdentifier;
            $pluginId = null;
        }

        if ($pluginId !== null) {
            $variant = $this->formService->getFormPluginVariant($formId, (int)$pluginId);
            if ($variant === null) {
                return null;
            }

            $overrideFinishers = (bool)($variant['dataSourceContext']['overrideFinishers'] ?? false);

            return new Typo3FormDataSource(
                $variant['formId'],
                $variant['formDefinition'],
                $variant['dataSourceContext'],
                $variant['overrideDocument'],
                applyOverride: $maintenanceMode || $overrideFinishers,
            );
        }

        // Base form
        $formDefinition = $this->formService->getFormById($formId);
        if ($formDefinition === null) {
            return null;
        }

        return new Typo3FormDataSource($formId, $formDefinition);
    }

    public function updateConfigurationDocument(DataSourceInterface $dataSource, string $document): void
    {
        if (!$dataSource instanceof Typo3FormDataSource) {
            return;
        }

        $context = $dataSource->getDataSourceContext();

        if (isset($context['pluginId'], $context['sheetIdentifier'])) {
            // Form plugin variant: update FlexForm sheet
            $this->formService->updateFormPluginDocument($document, (int)$context['pluginId'], $context['sheetIdentifier']);
        } else {
            // Base form: update form definition YAML
            $formId = $dataSource->getIdentifier();
            // Strip the type prefix to get the persistence identifier
            $formId = $this->getInnerIdentifier($formId);
            $this->formService->updateFormFinisherDocument($formId, $document);
        }
    }
}
