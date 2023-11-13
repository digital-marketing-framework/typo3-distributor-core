<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\File;

use DigitalMarketingFramework\Core\Model\File\FileInterface;
use TYPO3\CMS\Core\Resource\FileInterface as Typo3FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class File implements FileInterface
{
    /** @var Typo3FileInterface */
    protected $file;

    public function __construct(Typo3FileInterface $file)
    {
        $this->file = $file;
    }

    public function getName(): string
    {
        return $this->file->getName();
    }

    public function getPublicUrl(): string
    {
        return trim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/')
            . '/'
            . $this->file->getPublicUrl();
    }

    public function getRelativePath(): string
    {
        return $this->file->getStorage()->getUid() . ':' . $this->file->getIdentifier();
    }

    public function getMimeType(): string
    {
        return $this->file->getMimeType();
    }
}
