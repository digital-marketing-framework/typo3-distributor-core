<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form;

use DigitalMarketingFramework\Core\Model\Data\Value\ValueInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class FormElementProcessorEvent
{
    protected string|ValueInterface|null $result = null;

    protected bool $processed = false;

    protected ?string $name = null;

    /**
     * @param array<string,mixed> $configuration
     */
    public function __construct(
        protected RenderableInterface $element,
        protected mixed $value,
        protected array $configuration,
    ) {
    }

    public function getElementName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $name = $this->element->getIdentifier();
        if ($this->element instanceof FormElementInterface) {
            $properties = $this->element->getProperties();
            if (isset($properties['fluidAdditionalAttributes']['name'])) {
                $name = $properties['fluidAdditionalAttributes']['name'];
            }
        }

        return $name;
    }

    public function setElementName(?string $name): void
    {
        $this->name = $name;
    }

    public function getElement(): RenderableInterface
    {
        return $this->element;
    }

    public function getElementValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return array<string,mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function getProcessed(): bool
    {
        return $this->processed;
    }

    public function setResult(string|ValueInterface|null $result): void
    {
        $this->result = $result;
    }

    public function getResult(): string|ValueInterface|null
    {
        return $this->result;
    }
}
