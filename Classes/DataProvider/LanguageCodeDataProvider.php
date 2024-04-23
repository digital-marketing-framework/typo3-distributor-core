<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;

class LanguageCodeDataProvider extends DataProvider
{
    /**
     * @var string
     */
    public const KEY_FIELD = 'field';

    /**
     * @var string
     */
    public const DEFAULT_FIELD = 'language';

    protected function processContext(ContextInterface $context): void
    {
        $language = $GLOBALS['TSFE']->getLanguage()->getTwoLetterIsoCode();
        $this->submission->getContext()['language'] = $language;
    }

    protected function process(): void
    {
        $language = $this->submission->getContext()['language'] ?? null;
        if (isset($language)) {
            $this->setField(
                $this->getConfig(static::KEY_FIELD),
                $language
            );
        }
    }

    public static function getSchema(): SchemaInterface
    {
        /** @var ContainerSchema $schema */
        $schema = parent::getSchema();
        $schema->addProperty(static::KEY_FIELD, new StringSchema(static::DEFAULT_FIELD));

        return $schema;
    }
}
