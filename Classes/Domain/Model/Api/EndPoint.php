<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Api;

use DateTime;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Distributor\Core\Model\Api\EndPointInterface;
use JsonException;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class EndPoint extends AbstractEntity implements EndPointInterface
{
    public function __construct(
        protected string $pathSegment = '',
        protected string $configurationDocument = '',
    ) {
    }

    public function getPathSegment(): string
    {
        return $this->pathSegment;
    }

    public function setPathSegment(string $pathSegment): void
    {
        $this->pathSegment = $pathSegment;
    }

    public function getConfigurationDocument(): string
    {
        return $this->configurationDocument;
    }

    public function setConfigurationDocument(string $configurationDocument): void
    {
        $this->configurationDocument = $configurationDocument;
    }
}
