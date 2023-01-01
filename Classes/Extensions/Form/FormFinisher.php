<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form;

use DateTime;
use DigitalMarketingFramework\Core\ConfigurationDocument\ConfigurationDocumentManagerInterface;
use DigitalMarketingFramework\Distributor\Core\Model\Configuration\SubmissionConfiguration;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSet;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;

class FormFinisher extends AbstractFinisher
{
    protected ConfigurationDocumentManagerInterface $configurationDocumentManager;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'setup' => '',
    ];

    public function __construct(
        protected Registry $registry,
        protected FormDataProcessor $formDataProcessor,
    ) {
        $this->configurationDocumentManager = $registry->getConfigurationDocumentManager();
    }

    protected function getFormValues(array $configuration): array
    {
        $elements = $this->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getRenderablesRecursively();
        $elementValues = $this->finisherContext->getFormValues();
        return $this->formDataProcessor->process($elements, $elementValues, $configuration);
    }

    protected function getConfigurationStack(): array
    {
        $configurationDocument = $this->parseOption('setup');
        return $this->configurationDocumentManager->getConfigurationStackFromDocument($configurationDocument);
    }

    protected function debugLog(string $file, array $data): void
    {
        $timestamp = (new DateTime())->format('Y-m-d G:i:s T(P)');
        $path = Environment::getVarPath() . '/log/' . $file;
        try {
            $message = $timestamp . ':' . PHP_EOL . print_r($data, true) . PHP_EOL;
            file_put_contents($path, $message, FILE_APPEND);
        } catch (Exception $e) {
            $message = $timestamp . ': cannot log data' . PHP_EOL;
            @file_put_contents($path, $message, FILE_APPEND);
        }
    }

    protected function executeInternal(): ?string
    {
        // fetch configuration
        $configurationStack = $this->getConfigurationStack();
        $configuration = (new SubmissionConfiguration($configurationStack))->getDistributorConfiguration();

        // fetch form values
        $formValues = $this->getFormValues($configuration);

        // low level debug log, if configured
        if ($configuration[Registry::KEY_DEBUG_LOG] ?? Registry::DEFAULT_DEBUG_LOG) {
            $file = $configuration[Registry::KEY_DEBUG_LOG_FILE] ?? Registry::DEFAULT_DEBUG_LOG_FILE;
            $this->debugLog($file, $formValues);
        }

        // build and process submission
        $submission = new SubmissionDataSet($formValues, $configurationStack);
        $relay = $this->registry->getRelay();
        $relay->process($submission);

        return null;
    }
}
