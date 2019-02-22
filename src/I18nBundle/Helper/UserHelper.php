<?php

namespace I18nBundle\Helper;

use GeoIp2\Database\Reader;
use Symfony\Component\HttpFoundation\RequestStack;

class UserHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool|string
     */
    public function guessLanguage()
    {
        $masterRequest = $this->requestStack->getMasterRequest();

        $guessedLanguage = false;
        if ($masterRequest->server->has('HTTP_ACCEPT_LANGUAGE')) {
            $browserLanguage = substr($masterRequest->server->get('HTTP_ACCEPT_LANGUAGE'), 0, 2);
            if (!empty($browserLanguage)) {
                $guessedLanguage = $browserLanguage;
            }
        }

        return $guessedLanguage;
    }

    /**
     * @return bool|string
     */
    public function guessCountry()
    {
        $masterRequest = $this->requestStack->getMasterRequest();

        $geoDbFile = realpath(PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb');
        $record = null;

        $country = null;
        $userCountry = false;

        if (file_exists($geoDbFile)) {
            try {
                $reader = new Reader($geoDbFile);
                if ($masterRequest->server->has('HTTP_CLIENT_IP') &&
                    !empty($masterRequest->server->get('HTTP_CLIENT_IP'))) {
                    $ip = $masterRequest->server->get('HTTP_CLIENT_IP');
                } elseif ($masterRequest->server->has('HTTP_X_FORWARDED_FOR') &&
                    !empty($masterRequest->server->get('HTTP_X_FORWARDED_FOR'))) {
                    $ip = $masterRequest->server->get('HTTP_X_FORWARDED_FOR');
                } else {
                    $ip = $masterRequest->server->get('REMOTE_ADDR');
                }

                $record = $reader->city($ip);
                $country = $record->country->isoCode;
            } catch (\Exception $e) {
            }
        }

        if ($country !== false && !empty($country)) {
            $userCountry = strtoupper($country);
        }

        return $userCountry;
    }
}
