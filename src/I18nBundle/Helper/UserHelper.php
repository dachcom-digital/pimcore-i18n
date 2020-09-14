<?php

namespace I18nBundle\Helper;

use GeoIp2\Database\Reader;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
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
     * @return string[]
     */
    public function getLanguagesAcceptedByUser()
    {
        $masterRequest = $this->requestStack->getMasterRequest();
        if (!$masterRequest instanceof Request) {
            return [];
        }

        $guessedLanguages = [];
        $acceptLanguages = $masterRequest->getLanguages();

        if (!is_array($acceptLanguages)) {
            return $guessedLanguages;
        }

        $pimcoreLanguages = Tool::getValidLanguages();

        foreach ($acceptLanguages as $acceptLanguage) {
            $guessedLanguages = array_merge(
                $guessedLanguages,
                in_array($acceptLanguage, $pimcoreLanguages, true)
                    ? [$acceptLanguage]
                    : array_filter($pimcoreLanguages, function (string $pimcoreLanguage) use ($acceptLanguage) {
                    return substr($acceptLanguage, 0, 2) === substr($pimcoreLanguage, 0, 2);
                })
            );
        }

        return array_unique($guessedLanguages);
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
