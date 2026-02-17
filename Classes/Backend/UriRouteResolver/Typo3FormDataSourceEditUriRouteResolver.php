<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\UriRouteResolver;

use DigitalMarketingFramework\Core\Model\ConfigurationDocument\DataSourceMigratable;
use DigitalMarketingFramework\Typo3\Core\Backend\UriRouteResolver\Typo3UriRouteResolver;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource\Typo3FormDataSource;

class Typo3FormDataSourceEditUriRouteResolver extends Typo3UriRouteResolver
{
    /**
     * @var int
     */
    final public const WEIGHT = 0;

    protected function getRouteMatch(): string
    {
        return 'page.data-source.edit';
    }

    protected function match(string $route, array $arguments = []): bool
    {
        if (!parent::match($route, $arguments)) {
            return false;
        }

        $identifier = (string)($arguments['identifier'] ?? '');

        return str_starts_with($identifier, 'form:');
    }

    protected function doResolve(string $route, array $arguments = []): ?string
    {
        $identifier = (string)($arguments['identifier'] ?? '');
        $returnUrl = $this->getReturnUrl($arguments);

        // Try entity argument to avoid redundant lookup
        $entity = $arguments['entity'] ?? null;
        if ($entity instanceof DataSourceMigratable) {
            $dataSource = $entity->getDataSource();
            if ($dataSource instanceof Typo3FormDataSource) {
                $context = $dataSource->getDataSourceContext();
                if (isset($context['pluginId'])) {
                    return $this->buildRecordEditUrl('tt_content', $context['pluginId'], $returnUrl);
                }

                // Base form — form editor does not support returnUrl
                return (string)$this->getTypo3UriBuilder()->buildUriFromRoute('web_FormFormbuilder.FormEditor_index', [
                    'formPersistenceIdentifier' => $dataSource->getFormId(),
                ]);
            }
        }

        // Fallback: parse identifier to extract formId and optional pluginId
        $rest = substr($identifier, 5); // strip "form:"
        $lastColon = strrpos($rest, ':');
        if ($lastColon !== false) {
            $possiblePluginId = substr($rest, $lastColon + 1);
            if (ctype_digit($possiblePluginId)) {
                return $this->buildRecordEditUrl('tt_content', (int)$possiblePluginId, $returnUrl);
            }
        }

        // Base form — use form editor
        return (string)$this->getTypo3UriBuilder()->buildUriFromRoute('web_FormFormbuilder.FormEditor_index', [
            'formPersistenceIdentifier' => $rest,
        ]);
    }
}
