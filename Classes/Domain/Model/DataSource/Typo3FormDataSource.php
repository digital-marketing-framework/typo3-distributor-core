<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource;

use DigitalMarketingFramework\Core\SchemaDocument\FieldDefinition\FieldDefinition;
use DigitalMarketingFramework\Core\SchemaDocument\FieldDefinition\FieldListDefinition;
use DigitalMarketingFramework\Core\Utility\GeneralUtility;
use DigitalMarketingFramework\Distributor\Core\Model\DataSource\DistributorDataSource;

class Typo3FormDataSource extends DistributorDataSource
{
    /**
     * @var string
     */
    public const TYPE = 'form';

    /**
     * @param array<string,mixed> $formDefinition
     * @param array<string,mixed> $dataSourceContext
     */
    public function __construct(
        protected string $formId,
        protected array $formDefinition,
        protected array $dataSourceContext = [],
    ) {
        $name = $this->formDefinition['label'] ?? '';
        if (isset($this->dataSourceContext['pluginId'])) {
            $name .= ' (Plugin #' . $this->dataSourceContext['pluginId'] . ')';
        }

        $hash = GeneralUtility::calculateHash($this->formDefinition);
        $configurationDocument = '';
        foreach ($this->formDefinition['finishers'] ?? [] as $finisher) {
            if ($finisher['identifier'] === 'Digitalmarketingframework') {
                $configurationDocument = $finisher['options']['setup'] ?? '';
                break;
            }
        }

        $identifier = $formId;
        if (isset($this->dataSourceContext['pluginId'])) {
            $identifier .= ':' . $this->dataSourceContext['pluginId'];
        }

        parent::__construct(
            static::TYPE,
            static::TYPE . ':' . $identifier,
            $name,
            $hash,
            $configurationDocument
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getDataSourceContext(): array
    {
        return $this->dataSourceContext;
    }

    public function getBaseDataSourceIdentifier(): ?string
    {
        if (isset($this->dataSourceContext['pluginId'])) {
            return static::TYPE . ':' . $this->formId;
        }

        return null;
    }

    public function getDescription(): string
    {
        if (!isset($this->dataSourceContext['pluginId'])) {
            return '';
        }

        $pluginId = $this->dataSourceContext['pluginId'];
        $contentId = $this->dataSourceContext['contentId'] ?? $pluginId;

        $parts = [];

        if (isset($this->dataSourceContext['pageId'])) {
            $parts[] = 'Page #' . $this->dataSourceContext['pageId'];
        }

        // Content element: canonical ID, with actual record UID in brackets if different
        $contentPart = 'Content #' . $contentId;
        if ($pluginId !== $contentId) {
            $contentPart .= ' [#' . $pluginId . ']';
        }

        $parts[] = $contentPart;

        if (isset($this->dataSourceContext['languageName'])) {
            $contentPart = $this->dataSourceContext['languageName'];
            if (($this->dataSourceContext['languageId'] ?? 0) !== 0) {
                $contentPart .= ' [#' . $this->dataSourceContext['languageId'] . ']';
            }

            $parts[] = $contentPart;
        }

        if (isset($this->dataSourceContext['workspaceId'])) {
            $parts[] = 'Workspace #' . $this->dataSourceContext['workspaceId'];
        }

        if (isset($this->dataSourceContext['selectedFormId']) && $this->dataSourceContext['selectedFormId'] !== $this->formId) {
            $parts[] = 'Form Not Selected';
        }

        if (isset($this->dataSourceContext['overrideFinishers']) && !(bool)$this->dataSourceContext['overrideFinishers']) {
            $parts[] = 'Overrides Disabled';
        }

        return implode(', ', $parts);
    }

    public function canHaveVariants(): bool
    {
        return true;
    }

    /**
     * @param array<
     *   array{
     *     identifier:string,
     *     type:string,
     *     label?:string,
     *     properties?:array{
     *       options?:array<string,string>,
     *       fluidAdditionalAttributes?:array{name?:string,required?:"required"}
     *     },
     *     renderables?:array<array<mixed>>
     *   }
     * > $renderables
     */
    protected function readFields(array $renderables, FieldListDefinition $fieldListDefinition): void
    {
        foreach ($renderables as $renderable) {
            // we assume that every renderable is either a container or an input element, but never both
            if (isset($renderable['renderables'])) {
                $this->readFields($renderable['renderables'], $fieldListDefinition);
            } else {
                $name = $renderable['properties']['fluidAdditionalAttributes']['name'] ?? $renderable['identifier'];

                $type = FieldDefinition::TYPE_UNKNOWN;

                $label = $renderable['label'] ?? '';
                if ($label === '') {
                    $label = $name;
                }

                // TODO use default value when field definitions support it
                // $defaultValue = $renderable['defaultValue'] ?? null;

                $multiValue = null;

                $values = null;
                $options = $renderable['properties']['options'] ?? [];
                if ($options !== []) {
                    $values = array_keys($options);
                }

                $required = isset($renderable['properties']['fluidAdditionalAttributes']['required']);

                // TODO implement an entry point for external (typo3) plugins to identify their custom fields
                switch ($renderable['type']) {
                    case 'AdvancedPassword':
                    case 'Date':
                    case 'DatePicker':
                    case 'Email':
                    case 'Hidden':
                    case 'Password':
                    case 'RadioButton':
                    case 'SingleSelect':
                    case 'Telephone':
                    case 'Text':
                    case 'Textarea':
                    case 'Url':
                        $type = FieldDefinition::TYPE_STRING;
                        $multiValue = false;
                        break;
                    case 'FileUpload':
                    case 'ImageUpload':
                        $type = FieldDefinition::TYPE_STRING;
                        break;
                    case 'Number':
                        $type = FieldDefinition::TYPE_INTEGER;
                        $multiValue = false;
                        break;
                    case 'Checkbox':
                        $type = FieldDefinition::TYPE_BOOLEAN;
                        $multiValue = false;
                        break;
                    case 'MultiCheckbox':
                    case 'MultiSelect':
                        $type = FieldDefinition::TYPE_STRING;
                        $multiValue = true;
                        break;
                }

                $fieldListDefinition->addField(new FieldDefinition($name, $type, $label, $multiValue, values: $values, required: $required));
            }
        }
    }

    public function getFieldListDefinition(): FieldListDefinition
    {
        $fieldListDefinition = parent::getFieldListDefinition();
        $this->readFields($this->formDefinition['renderables'] ?? [], $fieldListDefinition);

        return $fieldListDefinition;
    }
}
