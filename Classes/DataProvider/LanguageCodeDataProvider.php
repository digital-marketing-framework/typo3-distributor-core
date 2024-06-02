<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\WriteableContextInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\StringSchema;
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
    public const KEY_DEFAULT_LANGUAGE = 'defaultLanguage';

    /**
     * @var string
     */
    public const DEFAULT_FIELD = 'language';

    protected function processContext(WriteableContextInterface $context): void
    {
        if (isset($context['language'])) {
            $language = $context['language'];
        } elseif (isset($GLOBALS['TSFE'])) {
            $language = $GLOBALS['TSFE']->getLanguage()->getTwoLetterIsoCode();
        } else {
            $language = $this->getConfig(static::KEY_DEFAULT_LANGUAGE);
        }

        if ($language !== '') {
            $context['language'] = $language;
        }
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

        $schema->addProperty(static::KEY_DEFAULT_LANGUAGE, new StringSchema());

        return $schema;
    }
}
