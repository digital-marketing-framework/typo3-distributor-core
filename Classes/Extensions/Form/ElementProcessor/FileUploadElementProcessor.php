<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Utility\GeneralUtility as DmfGeneralUtility;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
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
        Registry $registry
    ) {
        parent::__construct($logManager);
        $this->fileStorage = $registry->getFileStorage();
    }

    protected function getElementClass(): string
    {
        return FileUpload::class;
    }

    protected function disabled(): bool
    {
        return $this->configuration['fileUpload']['disableProcessing'] ?? false;
    }

    /**
     * @return array<string>
     */
    protected function prohibitedFileExtensions(): array
    {
        return DmfGeneralUtility::castValueToArray(strtolower($this->configuration['fileUpload']['prohibitedExtension'] ?? 'php,exe'));
    }

    protected function baseUploadPath(): string
    {
        return $this->configuration['fileUpload']['baseUploadPath'] ?? 'uploads/digital_marketing_framework/form_uploads/';
    }

    protected function override(): bool
    {
        // we want to override everything (with a null value) if file upload processing is disabled
        return $this->disabled();
    }

    protected function process(RenderableInterface $element, mixed $elementValue): mixed
    {
        if ($this->disabled()) {
            return null;
        }

        if ($elementValue === null) {
            return null;
        }

        if (!$element instanceof FileUpload) {
            throw new DigitalMarketingFrameworkException(sprintf('Field type FileUpload expected, found "%s".', $element::class));
        }

        if ($elementValue instanceof ExtbaseFileReference) {
            $elementValue = $elementValue->getOriginalResource();
        }

        if ($elementValue instanceof FileReference) {
            $elementValue = $elementValue->getOriginalFile();
        }

        if ($this->prohibitedFileExtensions() !== [] && in_array(strtolower((string)$elementValue->getExtension()), $this->prohibitedFileExtensions(), true)) {
            $this->logger->error(
                'Uploaded file did not pass safety checks, discarded',
                ['extension' => $elementValue->getExtension()]
            );

            return null;
        }

        $baseUploadPath = $this->baseUploadPath();
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
