<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\WriteableContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;

class AdwordsDataProvider extends DataProvider
{
    /**
     * @var string
     */
    public const COOKIE_KEYWORDS = 'adwords_keywords';

    /**
     * @var string
     */
    public const COOKIE_EVENTCODE = 'adwords_eventcode';

    protected function processContext(WriteableContextInterface $context): void
    {
        $context->copyCookieFromContext($this->context, static::COOKIE_KEYWORDS);
        $context->copyCookieFromContext($this->context, static::COOKIE_EVENTCODE);
    }

    protected function process(): void
    {
        // track LMS Keywords
        $keywords = $this->context->getCookie(static::COOKIE_KEYWORDS);
        if ($keywords !== null) {
            $this->setField(static::COOKIE_KEYWORDS, $keywords);
        }

        // track LMS Eventcode
        $eventCode = $this->context->getCookie(static::COOKIE_EVENTCODE);
        if ($eventCode !== null) {
            $this->setField(static::COOKIE_EVENTCODE, $eventCode);
        }
    }
}
