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
     * @var string
     */
    protected $geoIpDbPath;

    /**
     * @param RequestStack $requestStack
     * @param string       $geoIpDbPath
     */
    public function __construct(
        RequestStack $requestStack,
        string $geoIpDbPath
    ) {
        $this->requestStack = $requestStack;
        $this->geoIpDbPath = $geoIpDbPath;
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
        $record = null;
        $country = null;
        $userCountry = false;
        $masterRequest = $this->requestStack->getMasterRequest();

        if (file_exists($this->geoIpDbPath)) {
            try {
                $reader = new Reader($this->geoIpDbPath);
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
                // fail silently.
            }
        }

        if ($country !== false && !empty($country)) {
            $userCountry = strtoupper($country);
        }

        return $userCountry;
    }
}
