<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Domain\Model\DataSource;

use DigitalMarketingFramework\Core\SchemaDocument\FieldDefinition\FieldListDefinition;
use DigitalMarketingFramework\Core\Utility\GeneralUtility;
use DigitalMarketingFramework\Distributor\Core\Model\DataSource\DistributorDataSource;

class Typo3FormDataSource extends DistributorDataSource
{
    public const TYPE = 'form';

    /**
     * @param array<sting,mixed> $formDefinition
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

    public function getFieldListDefinition(): FieldListDefinition
    {
        // TODO read field definitions from form definition

        return parent::getFieldListDefinition();
    }
}
