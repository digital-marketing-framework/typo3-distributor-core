<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\DataProvider;

use DigitalMarketingFramework\Core\Context\WriteableContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Typo3\Distributor\Core\Utility\UtmzCookieParser;

class AdwordsCampaignsDataProvider extends DataProvider
{
    /**
     * @var array<string, string>
     */
    protected const UTMZ_MAP = [
        'utmz_source' => 'utmz_source',
        'utmz_medium' => 'utmz_medium',
        'utmz_campaign' => 'utmz_campaign',
        'utmz_term' => 'utmz_term',
        'utmz_content' => 'utmz_content',
    ];

    /**
     * @var array<string, string>
     */
    protected const UTM_MAP = [
        'ga_utm_source' => 'utm_source',
        'ga_utm_medium' => 'utm_medium',
        'ga_utm_campaign' => 'utm_campaign',
        'ga_utm_term' => 'utm_term',
        'ga_utm_content' => 'utm_content',
    ];

    protected function processContext(WriteableContextInterface $context): void
    {
        $context->copyCookieFromContext($this->context, '__utmz');
        foreach (array_keys(static::UTM_MAP) as $cookie) {
            $context->copyCookieFromContext($this->context, $cookie);
        }
    }

    protected function process(): void
    {
        $cookies = $this->context->getCookies();
        $utmz = new UtmzCookieParser($cookies);
        foreach (static::UTMZ_MAP as $member => $field) {
            $value = $utmz->getVar($member);
            if ($value !== null) {
                $this->setField($field, $value);
            }
        }

        foreach (static::UTM_MAP as $cookie => $field) {
            if (isset($cookies[$cookie])) {
                $this->setField($field, $cookies[$cookie]);
            }
        }
    }
}
