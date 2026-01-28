<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor;

use DigitalMarketingFramework\Core\GlobalConfiguration\GlobalConfigurationInterface;
use DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\FormElementProcessorEvent;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

abstract class ElementProcessor
{
    protected LoggerInterface $logger;

    protected GlobalConfigurationInterface $globalConfiguration;

    public function __construct(LogManagerInterface $logManager)
    {
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

    protected function match(RenderableInterface $element, mixed $elementValue): bool
    {
        $elementClass = $this->getElementClass();
        $elementType = $this->getElementType();
        $valueClass = $this->getValueClass();

        $result = false;
        if (
            ($elementClass !== '' && $element instanceof $elementClass)
            || ($elementType !== '' && $element->getType() === $elementType)
            || ($valueClass !== '' && is_a($elementValue, $valueClass))
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
        $this->globalConfiguration = $event->getGlobalConfiguration();
        $element = $event->getElement();
        $elementValue = $event->getElementValue();
        if ((!$event->getProcessed() || $this->override()) && $this->match($element, $elementValue)) {
            $result = $this->process($element, $elementValue);
            $event->setResult($result);
            $event->setProcessed(true);
        }
    }
}
