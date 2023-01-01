<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\FormElementProcessorEvent;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

abstract class ElementProcessor
{
    protected Logger $logger;

    protected array $configuration;

    public function __construct(
        protected ConfigurationManagerInterface $configurationManager,
        LogManagerInterface $logManager,
    ) {
        $this->logger = $logManager->getLogger(static::class);
    }

    abstract protected function process(RenderableInterface $element, mixed $elementValue): mixed;

    protected function getElementClass(): string
    {
        return '';
    }

    protected function getElementType(): string
    {
        return '';
    }

    protected function getValueClass(): string
    {
        return '';
    }

    protected function match($element, $elementValue): bool
    {
        $elementClass = $this->getElementClass();
        $elementType = $this->getElementType();
        $valueClass = $this->getValueClass();

        $result = false;
        if (
            ($elementClass && is_a($element, $elementClass))
            || ($elementType && $element->getType() === $elementType)
            || ($valueClass && is_a($elementValue, $valueClass))
         ) {
            $result = true;
        }
        return $result;
    }

    protected function override(): bool
    {
        return false;
    }

    public function __invoke(FormElementProcessorEvent $event): void
    {
        $this->configuration = $event->getConfiguration();
        $element = $event->getElement();
        $elementValue = $event->getElementValue();
        if ((!$event->getProcessed() || $this->override()) && $this->match($element, $elementValue)) {
            $result = $this->process($element, $elementValue);
            $event->setResult($result);
            $event->setProcessed(true);
        }
    }
}
