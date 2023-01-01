<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;

class AdwordsDataProvider extends DataProvider
{
    const COOKIE_KEYWORDS = 'adwords_keywords';
    const COOKIE_EVENTCODE = 'adwords_eventcode';

    protected function processContext(ContextInterface $context): void
    {
        $this->submission->getContext()->copyCookieFromContext($context, static::COOKIE_KEYWORDS);
        $this->submission->getContext()->copyCookieFromContext($context, static::COOKIE_EVENTCODE);
    }

    protected function process(): void
    {
        // track LMS Keywords
        $keywords = $this->submission->getContext()->getCookie(static::COOKIE_KEYWORDS);
        if ($keywords) {
            $this->setField(static::COOKIE_KEYWORDS, $keywords);
        }

        // track LMS Eventcode
        $eventCode = $this->submission->getContext()->getCookie(static::COOKIE_EVENTCODE);
        if ($eventCode) {
            $this->setField(static::COOKIE_EVENTCODE, $eventCode);
        }
    }
}
