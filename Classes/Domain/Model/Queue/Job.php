<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\Queue;

use DateTime;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use JsonException;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Job extends AbstractEntity implements JobInterface
{
    public function __construct(
        protected string $environment = '',
        protected DateTime $created = new DateTime(),
        protected DateTime $changed = new DateTime(),
        protected int $status = QueueInterface::STATUS_QUEUED,
        protected bool $skipped = false,
        protected string $statusMessage = '',
        protected string $serializedData = '',
        protected string $label = '',
        protected string $type = '',
        protected string $hash = '',
        protected int $retryAmount = 0,
    ) {
    }

    public function getId(): ?int
    {
        return $this->getUid();
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
        $labelParts = explode('#', $label);
        if (count($labelParts) > 1) {
            array_shift($labelParts);
            $this->setType(implode('#', $labelParts));
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getRetryAmount(): int
    {
        return $this->retryAmount;
    }

    public function setRetryAmount(int $amount): void
    {
        $this->retryAmount = $amount;
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

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
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

    public function setStatusMessage(string $message): void
    {
        $this->statusMessage = $message;
    }

    public function addStatusMessage(string $message): void
    {
        if ($message === '') {
            return;
        }

        $statusMessage = $this->getStatusMessage();
        if ($statusMessage !== '') {
            $statusMessage .= PHP_EOL . PHP_EOL;
        }

        $now = new DateTime();
        $statusMessage .= $now->format('Y-m-d H:i:s: ') . $message;

        $this->setStatusMessage($statusMessage);
    }

    public function getSerializedData(): string
    {
        return $this->serializedData;
    }

    public function setSerializedData(string $serializedData): void
    {
        $this->serializedData = $serializedData;
    }

    public function getData(): array
    {
        $data = $this->getSerializedData();
        if ($data === '') {
            return [];
        }

        try {
            return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    public function setData(array $data): void
    {
        try {
            $serializedData = json_encode($data, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->setStatus(QueueInterface::STATUS_FAILED);
            $this->setStatusMessage(sprintf('data encoding failed [%d]: "%s"', $e->getCode(), $e->getMessage()));
            try {
                $serializedData = json_encode($data, flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $serializedData = print_r($data, true);
            }
        }

        $this->setSerializedData($serializedData);
    }
}
