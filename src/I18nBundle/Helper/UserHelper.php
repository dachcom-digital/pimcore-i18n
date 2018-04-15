<?php

namespace I18nBundle\Helper;

use GeoIp2\Database\Reader;

class UserHelper
{
    /**
     * @return bool|string
     */
    public function guessLanguage()
    {
        $guessedLanguage = false;
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (!empty($browserLanguage)) {
                $guessedLanguage = $browserLanguage;
            }
        }

        //return 'en';
        return $guessedLanguage;
    }

    /**
     * @return bool|string
     */
    public function guessCountry()
    {
        $geoDbFile = realpath(PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb');
        $record = null;

        $country = null;
        $userCountry = false;

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
                //$ip = '52.33.249.128';    //us

                $record = $reader->city($ip);
                $country = $record->country->isoCode;
            } catch (\Exception $e) {
            }
        }

        if ($country !== false && !empty($country)) {
            $userCountry = strtoupper($country);
        }

        //return 'US';
        return $userCountry;
    }
}