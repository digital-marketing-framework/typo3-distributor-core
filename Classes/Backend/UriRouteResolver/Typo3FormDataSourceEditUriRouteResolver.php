<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Backend\UriRouteResolver;

use DigitalMarketingFramework\Core\Model\ConfigurationDocument\DataSourceMigratable;
use DigitalMarketingFramework\Typo3\Core\Backend\UriRouteResolver\Typo3UriRouteResolver;
use DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource\Typo3FormDataSource;
use TYPO3\CMS\Core\Information\Typo3Version;

class Typo3FormDataSourceEditUriRouteResolver extends Typo3UriRouteResolver
{
    /**
     * TYPO3 14 split the form module into separate manager/editor modules.
     * v13: combined module hosts the editor's index action at this route name.
     * v14: editor is its own module with route name 'form_editor'.
     */
    protected function getFormEditorRouteName(): string
    {
        return (new Typo3Version())->getMajorVersion() >= 14
            ? 'form_editor'
            : 'web_FormFormbuilder.FormEditor_index';
    }

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

                // Base form — form editor does not honour returnUrl in a way that
                // updates the backend chrome (sidebar stays stale on close in v14).
                // Hoping for a fix in a later v14 patch or v15.
                return (string)$this->getTypo3UriBuilder()->buildUriFromRoute($this->getFormEditorRouteName(), [
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

        // Base form — see returnUrl comment above
        return (string)$this->getTypo3UriBuilder()->buildUriFromRoute($this->getFormEditorRouteName(), [
            'formPersistenceIdentifier' => $rest,
        ]);
    }
}
