<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form;

use DigitalMarketingFramework\Core\Model\Data\Value\ValueInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class FormDataProcessor
{
    protected Logger $logger;

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        LogManagerInterface $logManager,
    ) {
        $this->logger = $logManager->getLogger(static::class);
    }

    /**
     * @param array<RenderableInterface> $elements
     * @param array<mixed> $values
     * @param array<mixed> $configuration
     * @return array<string,string|ValueInterface>
     */
    public function process(array $elements, array $values, array $configuration): array
    {
        $result = [];
        foreach ($elements as $element) {
            $type = $element->getType();
            $id = $element->getIdentifier();
            $value = $values[$id] ?? null;

            // default element processors are within the namespace
            // \DigitalMarketingFramework\Typo3\Distributor\Core\Extensions\Form\ElementProcessor
            $event = new FormElementProcessorEvent($element, $value, $configuration);
            $this->eventDispatcher->dispatch($event);
            if (!$event->getProcessed()) {
                $this->logger->error('Ignoring unknown form field type.', [
                    'form' => $element->getRootForm()->getIdentifier(),
                    'field' => $id,
                    'class' => get_class($element),
                    'type' => $type,
                ]);
            } elseif ($event->getResult() !== null) {
                $result[$event->getElementName()] = $event->getResult();
            }
        }
        return $result;
    }
}
