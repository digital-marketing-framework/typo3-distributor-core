<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource;

use DigitalMarketingFramework\Core\SchemaDocument\FieldDefinition\FieldDefinition;
use DigitalMarketingFramework\Core\SchemaDocument\FieldDefinition\FieldListDefinition;
use DigitalMarketingFramework\Core\Utility\GeneralUtility;
use DigitalMarketingFramework\Distributor\Core\Model\DataSource\DistributorDataSource;

class Typo3FormDataSource extends DistributorDataSource
{
    public const TYPE = 'form';

    /**
     * @param array<string,mixed> $formDefinition
     */
    public function __construct(
        protected string $formId,
        protected array $formDefinition,
    ) {
        $name = $this->formDefinition['label'] ?? '';
        $hash = GeneralUtility::calculateHash($this->formDefinition);
        $configurationDocument = '';
        foreach ($this->formDefinition['finishers'] ?? [] as $finisher) {
            if ($finisher['identifier'] === 'Digitalmarketingframework') {
                $configurationDocument = $finisher['options']['setup'];
                break;
            }
        }

        parent::__construct(
            static::TYPE,
            static::TYPE . ':' . $formId,
            $name,
            $hash,
            $configurationDocument
        );
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

                switch ($renderable['type']) {
                    case 'Email':
                    case 'Text':
                    case 'Telephone':
                    case 'Date':
                    case 'Password':
                    case 'Textarea':
                    case 'SingleSelect':
                    case 'RadioButton':
                    case 'Url':
                    case 'DatePicker':
                    case 'Hidden':
                    case 'AdvanedPassword':
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
                    case 'MultiSelect':
                    case 'MultiCheckbox':
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
