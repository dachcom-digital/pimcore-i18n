<?php

namespace I18nBundle\User;

use I18nBundle\Configuration\Configuration;
use GeoIp2\Database\Reader;

class I18nGuesser {

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $validLanguages;

    /**
     * I18nGuesser constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->validLanguages = \Pimcore\Tool::getValidLanguages();
    }

    /**
     * @return bool|string
     */
    public function guessLanguage()
    {
        $guessedLanguage = FALSE;

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($browserLanguage, $this->validLanguages)) {
                $guessedLanguage = $browserLanguage;
            }
        }

        return $guessedLanguage;
    }

    /**
     * @return bool|mixed|string
     */
    public function guessCountry()
    {
        $geoDbFile = realpath(PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb');
        $record = NULL;

        $country = NULL;
        $userCountry = FALSE;

        if (file_exists($geoDbFile)) {
            try {
                $reader = new Reader($geoDbFile);

                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }

                //$ip = '21 59.148.0.0';    //hong kong
                //$ip = '31.5.255.255';     //belgium
                //$ip = '194.166.128.22';   //austria
                //$ip = '188.142.192.35';   //hungary
                //$ip = '5.148.191.255';    //swiss
                //$ip = '46.162.191.255';   //france

                $record = $reader->city($ip);
                $country = $record->country->isoCode;
            } catch (\Exception $e) {
            }
        }

        if ($country !== FALSE && !empty($country)) {
            $countryCode = strtoupper($country);

            $validCountries = $this->configuration->getCountryAdapter()->getActiveCountries();
            if (isset($validCountries[$countryCode])) {
                $userCountry = strtolower($countryCode);
            }
        }

        if ($userCountry === FALSE) {
            $globalPrefix = $this->configuration->getConfig('globalPrefix');
            return $globalPrefix !== FALSE ? $globalPrefix : FALSE;
        }

        return $userCountry;
    }
}