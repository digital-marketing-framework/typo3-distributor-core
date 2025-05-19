<?php

namespace DigitalMarketingFramework\Typo3\Distributor\Core\Utility;

/**
 * UTMZ Cookie Parser parses values from Google Analytics cookies into variables
 * for population into hidden fields, databases or elsewhere
 * see http://daleconboy.com/portfolio/code/google-utmz-cookie-parser for more information
 */
class UtmzCookieParser
{
    protected ?string $utmz_source = null;

    protected ?string $utmz_medium = null;

    protected ?string $utmz_term = null;

    protected ?string $utmz_content = null;

    protected ?string $utmz_campaign = null;

    protected ?string $utmz_gclid = null;

    protected ?string $utmz = null;

    protected ?string $utmz_domainHash = null;

    protected ?string $utmz_timestamp = null;

    protected ?string $utmz_sessionNumber = null;

    protected ?string $utmz_campaignNumber = null;

    /**
     * Constructor fires method that parses and assigns property values
     *
     * @param array<string,string> $cookies
     */
    public function __construct(array $cookies)
    {
        $this->setUtmz($cookies);
    }

    public function getVar(string $name): ?string
    {
        return match ($name) {
            'utmz_source' => $this->utmz_source,
            'utmz_medium' => $this->utmz_medium,
            'utmz_term' => $this->utmz_term,
            'utmz_content' => $this->utmz_content,
            'utmz_campaign' => $this->utmz_campaign,
            'utmz_gclid' => $this->utmz_gclid,
            'utmz' => $this->utmz,
            'utmz_domainHash' => $this->utmz_domainHash,
            'utmz_timestamp' => $this->utmz_timestamp,
            'utmz_sessionNumber' => $this->utmz_sessionNumber,
            'utmz_campaignNumber' => $this->utmz_campaignNumber,
            default => null,
        };
    }

    /**
     * Grab utmz cookie if it exists
     *
     * @param array<string,string> $cookies
     */
    private function setUtmz(array $cookies): void
    {
        if (isset($cookies['__utmz'])) {
            $this->utmz = $cookies['__utmz'];
            $this->parseUtmz();
        }
    }

    /**
     * parse utmz cookie into variables
     */
    private function parseUtmz(): void
    {
        // Break cookie in half
        if (str_starts_with((string)$this->utmz, 'u')) {
            // starts with a "u" means ther is no first half
            $utmz_a = '';
            $utmz_b = $this->utmz;
        } else {
            $utmz_b = strstr((string)$this->utmz, 'u');
            $utmz_a = substr((string)$this->utmz, 0, strpos((string)$this->utmz, (string)$utmz_b) - 1);
        }

        // assign variables to first half of cookie
        $utmz_a_list = explode('.', $utmz_a);
        $this->utmz_domainHash = $utmz_a_list[0] ?? '';
        $this->utmz_timestamp = $utmz_a_list[1] ?? '';
        $this->utmz_sessionNumber = $utmz_a_list[2] ?? '';
        $this->utmz_campaignNumber = $utmz_a_list[3] ?? '';

        // break apart second half of cookie
        $utmzPairs = [];
        $z = explode('|', (string)$utmz_b);
        foreach ($z as $value) {
            $v = explode('=', $value);
            $pairKey = $v[0] ?? '';
            $pairValue = $v[1] ?? '';
            if ($pairKey !== '' && $pairValue !== '') {
                $utmzPairs[$v[0]] = $v[1];
            }
        }

        // Variable assignment for second half of cookie
        foreach ($utmzPairs as $key => $value) {
            switch ($key) {
                case 'utmcsr':
                    $this->utmz_source = $value;
                    break;
                case 'utmcmd':
                    $this->utmz_medium = $value;
                    break;
                case 'utmctr':
                    $this->utmz_term = $value;
                    break;
                case 'utmcct':
                    $this->utmz_content = $value;
                    break;
                case 'utmccn':
                    $this->utmz_campaign = $value;
                    break;
                case 'utmgclid':
                    $this->utmz_gclid = $value;
                    break;
                default:
                    // do nothing
            }
        }
    }
}
