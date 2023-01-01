<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Registry;

use DigitalMarketingFramework\Distributor\Core\Model\Configuration\SubmissionConfigurationInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\Registry as CoreDistributorRegistry;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryGlobalConfigurationUpdateEvent;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryPluginUpdateEvent;
use DigitalMarketingFramework\Typo3\Core\Registry\Event\CoreRegistryServiceUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryPluginUpdateEvent;
use DigitalMarketingFramework\Typo3\Distributor\Core\Registry\Event\DistributorRegistryServiceUpdateEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

class Registry extends CoreDistributorRegistry implements SingletonInterface
{
    public const KEY_PID = 'pid';
    public const DEFAULT_PID = null;

    public const KEY_DEBUG_LOG = 'debugLog';
    public const DEFAULT_DEBUG_LOG = false;

    public const KEY_DEBUG_LOG_FILE = 'debugLogFile';
    public const DEFAULT_DEBUG_LOG_FILE = 'ditigal-marketing-framework-distributor-submission.log';

    public const KEY_FILE_UPLOAD_DISABLE_PROCESSING = 'disableProcessing';
    public const DEFAULT_FILE_UPLOAD_DISABLE_PROCESSING = false;

    public const KEY_FILE_UPLOAD_BASE_UPLOAD_PATH = 'baseUploadPath';
    public const DEFAULT_FILE_UPLOAD_BASE_UPLOAD_PATH = 'uploads/digital_marketing_framework/form_uploads/';

    public const KEY_FILE_UPLOAD_PROHIBITED_EXTENSION = 'prohibitedExtension';
    public const DEFAULT_FILE_UPLOAD_PROHIBITED_EXTENSION = 'php,exe';

    public const KEY_FILE_UPLOAD = 'fileUpload';

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    public function initializeObject(): void
    {
        $this->eventDispatcher->dispatch(
            new CoreRegistryGlobalConfigurationUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryServiceUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryPluginUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new DistributorRegistryServiceUpdateEvent($this)
        );
        $this->eventDispatcher->dispatch(
            new DistributorRegistryPluginUpdateEvent($this)
        );
    }

    public function getDefaultConfiguration(): array
    {
        $defaultConfiguration = parent::getDefaultConfiguration();

        $defaultConfiguration[SubmissionConfigurationInterface::KEY_DISTRIBUTOR][static::KEY_PID] = static::DEFAULT_PID;

        $defaultConfiguration[SubmissionConfigurationInterface::KEY_DISTRIBUTOR][static::KEY_FILE_UPLOAD] = [
            static::KEY_FILE_UPLOAD_DISABLE_PROCESSING => static::DEFAULT_FILE_UPLOAD_DISABLE_PROCESSING,
            static::KEY_FILE_UPLOAD_BASE_UPLOAD_PATH => static::DEFAULT_FILE_UPLOAD_BASE_UPLOAD_PATH,
            static::KEY_FILE_UPLOAD_PROHIBITED_EXTENSION => static::DEFAULT_FILE_UPLOAD_PROHIBITED_EXTENSION,
        ];

        $defaultConfiguration[SubmissionConfigurationInterface::KEY_DISTRIBUTOR][static::KEY_DEBUG_LOG] = static::DEFAULT_DEBUG_LOG;
        $defaultConfiguration[SubmissionConfigurationInterface::KEY_DISTRIBUTOR][static::KEY_DEBUG_LOG_FILE] = static::DEFAULT_DEBUG_LOG_FILE;

        return $defaultConfiguration;
    }

    public function getConfigurationSchema(): array
    {
        $configurationSchema = parent::getConfigurationSchema();
        // TODO add typo3 specific config schema
        return $configurationSchema;
    }
}
