<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\ViewHelpers\Be;

use Closure;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MergeFiltersViewHelper extends AbstractViewHelper
{
    /**
     * @var string[]
     */
    protected const FILTER_ATTRIBUTES = ['status', 'type'];

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('filters', 'array', 'filters to be merged into');
        $this->registerArgument('type', 'array', 'type list to merge', false, []);
        $this->registerArgument('status', 'array', 'status list to merge', false, []);
        $this->registerArgument('additionalFilters', 'array', 'additional filters to merge', false, []);
    }

    /**
     * @phpstan-ignore-next-line TYPO3's AbstractViewHelper::renderStatic(), which returns `mixed`, conflicts with its ViewHelperInterface::renderStatic() which returns `string`.
     *
     * @return array<string,mixed>
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): array {
        $filters = $arguments['filters'];
        foreach (static::FILTER_ATTRIBUTES as $attribute) {
            $attributeValues = array_filter($arguments[$attribute] ?? []);
            $filters[$attribute] = [];
            if ($attributeValues === []) {
                unset($filters[$attribute]);
            } else {
                foreach ($attributeValues as $value) {
                    $filters[$attribute][$value] = 1;
                }
            }
        }

        foreach ($arguments['additionalFilters'] as $key => $value) {
            $filters[$key] = $value;
        }

        return $filters;
    }
}
