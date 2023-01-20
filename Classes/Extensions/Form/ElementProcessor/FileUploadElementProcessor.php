<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Core\Model\Data\Value\FileValue;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\File\File;
use DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\FormElementProcessorEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Registry;
use Exception;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class FileUploadElementProcessor extends ElementProcessor
{
    public function __construct(
        ConfigurationManagerInterface $configurationManager,
        LogManagerInterface $logManager,
        protected ResourceFactory $resourceFactory,
    ) {
        parent::__construct($configurationManager, $logManager);
    }

    protected function getElementClass(): string
    {
        return FileUpload::class;
    }

    protected function disabled(): bool
    {
        return $this->configuration['fileUpload']['disableProcessing'] ?? false;
    }

    protected function prohibitedFileExtensions(): array
    {
        $prohibitedExtensions = $this->configuration['fileUpload']['prohibitedExtension'] ?? 'php,exe';
        return $prohibitedExtensions ? explode(',', $prohibitedExtensions) : [];
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

        if ($elementValue instanceof ExtbaseFileReference) {
            $elementValue = $elementValue->getOriginalResource();
        }

        if ($elementValue instanceof FileReference) {
            $elementValue = $elementValue->getOriginalFile();
        }

        if (!empty($this->prohibitedFileExtensions())) {
            if (in_array($elementValue->getExtension(), $this->prohibitedFileExtensions())) {
                $this->logger->error(
                    'Uploaded file did not pass safety checks, discarded',
                    ['extension' => $elementValue->getExtension()]
                );
                return null;
            }
        }
        $defaultStorage = $this->resourceFactory->getDefaultStorage();

        $baseUploadPath = rtrim($this->baseUploadPath(), '/')
            . '/' . $element->getRootForm()->getIdentifier() . '/';
        $folderName = $elementValue->getSha1() . random_int(10000, 99999) . '/';

        $folderObject = $this->resourceFactory->createFolderObject(
            $defaultStorage,
            $baseUploadPath . $folderName,
            $folderName
        );

        try {
            $folder = $defaultStorage->getFolder($folderObject->getIdentifier());
        } catch (Exception $e) {
            try {
                $folder = $defaultStorage->createFolder($folderObject->getIdentifier());
            } catch (Exception $e) {
                $this->logger->error(
                    'UploadFormField folder for this form can not be created',
                    ['baseUploadPath' => $baseUploadPath]
                );
                return null;
            }
        }

        $fileName = $elementValue->getName();
        $copiedFile = $elementValue->copyTo($folder);

        if ($copiedFile) {
            if ($copiedFile instanceof FileInterface) {
                /** @var File $file */
                $file = GeneralUtility::makeInstance(File::class, $copiedFile);

                /** @var FileValue $uploadField */
                $uploadField = GeneralUtility::makeInstance(FileValue::class, $file);
                $uploadField->setFileName($fileName);
                return $uploadField;
            }
        } else {
            $this->logger->error(
                'Failed to copy uploaded file "' . $fileName . '" to destination "' . $folder->getIdentifier() . '"!',
                [
                    'fileName' => $fileName,
                    'destination' => $folder->getIdentifier(),
                ]
            );
        }
        return null;
    }
}
