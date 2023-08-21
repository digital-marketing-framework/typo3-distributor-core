<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue;

use DateTime;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Job extends AbstractEntity implements JobInterface
{
    public function __construct(
        protected DateTime $created = new DateTime(),
        protected DateTime $changed = new DateTime(),
        protected int $status = QueueInterface::STATUS_QUEUED,
        protected bool $skipped = false,
        protected string $statusMessage = '',
        protected string $serializedData = '',
        protected string $routeId = '',
        protected string $label = '',
        protected string $hash = '',
    ) {
    }

    protected function updateMetaData()
    {
        $data = $this->getData();
        if (!empty($data)) {
            if (isset($data['routeId'])) {
                $this->setRouteId($data['routeId']);
            }
        }
    }

    public function getId(): int
    {
        return $this->uid;
    }

    public function setId(int $id): void
    {
        $this->uid = $id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getRouteId(): string
    {
        return $this->routeId;
    }

    public function setRouteId(string $routeId): void
    {
        $this->routeId = $routeId;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): void
    {
        $this->created = $created;
    }

    public function getChanged(): DateTime
    {
        return $this->changed;
    }

    public function setChanged(DateTime $changed): void
    {
        $this->changed = $changed;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getSkipped(): bool
    {
        return $this->skipped;
    }

    public function setSkipped(bool $skipped): void
    {
        $this->skipped = $skipped;
    }

    public function getStatusMessage(): string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(string $statusMessage): void
    {
        $this->statusMessage = $statusMessage;
    }

    public function getSerializedData(): string
    {
        return $this->serializedData;
    }

    public function setSerializedData(string $serializedData): void
    {
        $this->serializedData = $serializedData;
        $this->updateMetaData();
    }

    public function getData(): array
    {
        $data = $this->getSerializedData();
        if (!$data) {
            return [];
        }
        $data = json_decode($data, true);
        if (!$data) {
            return [];
        }
        return $data;
    }

    public function setData(array $data): void
    {
        $serializedData = json_encode($data);
        if ($serializedData === false) {
            $this->setStatus(QueueInterface::STATUS_FAILED);
            $this->setStatusMessage('data encoding failed [' . json_last_error() . ']: "' . json_last_error_msg() . '"');

            $serializedData = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($serializedData === false) {
                if (isset($data['submission']['configuration'])) {
                    // remove "configuration" since print_r is not able to print big data sets completely
                    // and "data" and "context" are much more important (and usually much smaller)
                    unset($data['submission']['configuration']);
                }
                $serializedData = print_r($data, true);
            }
        }
        $this->setSerializedData($serializedData);
    }
}
