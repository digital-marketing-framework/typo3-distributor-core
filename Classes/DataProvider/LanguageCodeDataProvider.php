<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;

class LanguageCodeDataProvider extends DataProvider
{
    const KEY_FIELD = 'field';
    const DEFAULT_FIELD = 'language';

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

    public static function getDefaultConfiguration(): array
    {
        return parent::getDefaultConfiguration() + [
            static::KEY_FIELD => static::DEFAULT_FIELD,
        ];
    }
}
