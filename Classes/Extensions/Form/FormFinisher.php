<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form;

use DateTime;
use DigitalMarketingFramework\Core\ConfigurationDocument\ConfigurationDocumentManagerInterface;
use DigitalMarketingFramework\Core\Model\Data\Value\ValueInterface;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSet;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use DigitalMarketingFramework\Typo3\Distributor\Core\DataSource\Typo3FormService;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource\Typo3FormDataSource;
use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;

class FormFinisher extends AbstractFinisher
{
    protected RegistryInterface $registry;

    protected ConfigurationDocumentManagerInterface $configurationDocumentManager;

    /**
     * @var array<mixed>
     */
    protected $defaultOptions = [
        'setup' => '',
    ];

    public function __construct(
        protected Typo3FormService $formService,
        protected FormDataProcessor $formDataProcessor,
        RegistryCollection $registryCollection,
    ) {
        $this->registry = $registryCollection->getRegistryByClass(RegistryInterface::class);
        $this->configurationDocumentManager = $this->registry->getConfigurationDocumentManager();
    }

    protected function getFormDataSourceId(): string
    {
        return Typo3FormDataSource::TYPE . ':' . $this->finisherContext->getFormRuntime()->getFormDefinition()->getPersistenceIdentifier();
    }

    /**
     * @param array<string,mixed> $configuration
     *
     * @return array<string,string|ValueInterface>
     */
    protected function getFormValues(array $configuration): array
    {
        $elements = $this->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getRenderablesRecursively();
        $elementValues = $this->finisherContext->getFormValues();

        return $this->formDataProcessor->process($elements, $elementValues, $configuration);
    }

    /**
     * @return array<array<string,mixed>>
     */
    protected function getConfigurationStack(): array
    {
        $configurationDocument = $this->parseOption('setup');

        return $this->configurationDocumentManager->getConfigurationStackFromDocument($configurationDocument);
    }

    /**
     * @deprecated
     *
     * @param array<mixed> $data
     */
    protected function debugLog(string $file, array $data): void
    {
        $timestamp = (new DateTime())->format('Y-m-d G:i:s T(P)');
        $path = Environment::getVarPath() . '/log/' . $file;
        try {
            $message = $timestamp . ':' . PHP_EOL . print_r($data, true) . PHP_EOL;
            file_put_contents($path, $message, FILE_APPEND);
        } catch (Exception) {
            $message = $timestamp . ': cannot log data' . PHP_EOL;
            @file_put_contents($path, $message, FILE_APPEND);
        }
    }

    protected function executeInternal(): ?string
    {
        // fetch global configuration
        $globalConfiguration = $this->registry->getGlobalConfiguration()->get('dmf_distributor_core') ?? [];

        // fetch configuration
        $configurationStack = $this->getConfigurationStack();

        // fetch form values
        $formValues = $this->getFormValues($globalConfiguration);

        // compute data source ID
        $dataSourceId = $this->getFormDataSourceId();

        $dataSourceContext = $this->formService->getFormDataSourceContext($this->finisherContext->getRequest());

        // low level debug log, if configured
        if (isset($globalConfiguration['debug']['enabled']) && (bool)$globalConfiguration['debug']['enabled']) {
            $file = $globalConfiguration['debug']['file'] ?? 'digital-marketing-framework-distributor-submission.log';
            $this->debugLog($file, $formValues);
        }

        // build and process submission
        $submission = new SubmissionDataSet($dataSourceId, $dataSourceContext, $formValues, $configurationStack);
        $submission->getContext()->setResponsive(true);
        $relay = $this->registry->getDistributor();
        $relay->process($submission);
        $submission->getContext()->applyResponseData();

        return null;
    }
}
