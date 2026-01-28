<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\GlobalConfiguration\Settings\DistributorFileUploadSettings;
use DigitalMarketingFramework\Typo3\Core\Registry\RegistryCollection;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class FileUploadElementProcessor extends ElementProcessor
{
    protected FileStorageInterface $fileStorage;

    public function __construct(
        LogManagerInterface $logManager,
        RegistryCollection $registryCollection,
    ) {
        parent::__construct($logManager);
        $registry = $registryCollection->getRegistryByClass(RegistryInterface::class);
        $this->fileStorage = $registry->getFileStorage();
    }

    protected function getElementClass(): string
    {
        return FileUpload::class;
    }

    protected function getFileUploadSettings(): DistributorFileUploadSettings
    {
        return $this->globalConfiguration->getGlobalSettings(DistributorFileUploadSettings::class);
    }

    protected function override(): bool
    {
        // we want to override everything (with a null value) if file upload processing is disabled
        return $this->getFileUploadSettings()->disableProcessing();
    }

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        $settings = $this->getFileUploadSettings();

        if ($settings->disableProcessing()) {
            return null;
        }

        if ($elementValue === null) {
            return null;
        }

        if (!$element instanceof FileUpload) {
            throw new DigitalMarketingFrameworkException(sprintf('Field type FileUpload expected, found "%s".', $element::class), 8544485185);
        }

        if ($elementValue instanceof ExtbaseFileReference) {
            $elementValue = $elementValue->getOriginalResource();
        }

        if ($elementValue instanceof FileReference) {
            $elementValue = $elementValue->getOriginalFile();
        }

        $prohibitedExtensions = $settings->getProhibitedExtensions();
        if ($prohibitedExtensions !== [] && in_array(strtolower((string)$elementValue->getExtension()), $prohibitedExtensions, true)) {
            $this->logger->error(
                'Uploaded file did not pass safety checks, discarded',
                ['extension' => $elementValue->getExtension()]
            );

            return null;
        }

        $baseUploadPath = $settings->getBaseUploadPath();
        $folderIdentifier = rtrim($baseUploadPath, '/')
                . '/' . $element->getRootForm()->getIdentifier() . '/'
                . $elementValue->getSha1() . random_int(10000, 99999) . '/';
        $fileIdentifier = $elementValue->getCombinedIdentifier();
        try {
            if (!$this->fileStorage->folderExists($folderIdentifier)) {
                $this->fileStorage->createFolder($folderIdentifier);
            }

            $copiedFileIdentifier = $this->fileStorage->copyFileToFolder(
                $fileIdentifier,
                $folderIdentifier
            );

            $fileValue = $this->fileStorage->getFileValue($copiedFileIdentifier);
            $fileValue->setFileName($elementValue->getName());

            return $fileValue;
        } catch (DigitalMarketingFrameworkException $e) {
            $this->logger->error(
                'Failed to copy uploaded file: "' . $e->getMessage() . '"',
                [
                    'file' => $fileIdentifier,
                    'folder' => $folderIdentifier,
                ]
            );

            return null;
        }
    }
}
