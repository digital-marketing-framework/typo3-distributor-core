<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Utility\UtmzCookieParser;

class AdwordsCampaignsDataProvider extends DataProvider
{
    protected const UTMZ_MAP = [
        'utmz_source' => 'utmz_source',
        'utmz_medium' => 'utmz_medium',
        'utmz_campaign' => 'utmz_campaign',
        'utmz_term' => 'utmz_term',
        'utmz_content' => 'utmz_content',
    ];

    protected const UTM_MAP = [
        'ga_utm_source' => 'utm_source',
        'ga_utm_medium' => 'utm_medium',
        'ga_utm_campaign' => 'utm_campaign',
        'ga_utm_term' => 'utm_term',
        'ga_utm_content' => 'utm_content',
    ];

    protected function processContext(ContextInterface $context): void
    {
        $this->submission->getContext()->copyCookieFromContext($context, '__utmz');
        foreach (array_keys(static::UTM_MAP) as $cookie) {
            $this->submission->getContext()->copyCookieFromContext($context, $cookie);
        }
    }

    protected function process(): void
    {
        $cookies = $this->submission->getContext()->getCookies();
        $utmz = new UtmzCookieParser($cookies);
        foreach (static::UTMZ_MAP as $member => $field) {
            if ($utmz->$member) {
                $this->setField($field, $utmz->$member);
            }
        }

        foreach (static::UTM_MAP as $cookie => $field) {
            if (isset($cookies[$cookie])) {
                $this->setField($field, $cookies[$cookie]);
            }
        }
    }
}
