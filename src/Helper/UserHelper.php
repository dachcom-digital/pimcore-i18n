<?php

namespace I18nBundle\Helper;

use GeoIp2\Database\Reader;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserHelper
{
    protected RequestStack $requestStack;
    protected string $geoIpDbPath;

    public function __construct(
        RequestStack $requestStack,
        string $geoIpDbPath
    ) {
        $this->requestStack = $requestStack;
        $this->geoIpDbPath = $geoIpDbPath;
    }

    public function getLanguagesAcceptedByUser(): array
    {
        $masterRequest = $this->requestStack->getMainRequest();
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

    public function guessCountry(): ?string
    {
        $country = null;
        $userCountry = null;
        $mainRequest = $this->requestStack->getMainRequest();

        if (file_exists($this->geoIpDbPath)) {
            try {
                $reader = new Reader($this->geoIpDbPath);
                if ($mainRequest->server->has('HTTP_CLIENT_IP') &&
                    !empty($mainRequest->server->get('HTTP_CLIENT_IP'))) {
                    $ip = $mainRequest->server->get('HTTP_CLIENT_IP');
                } elseif ($mainRequest->server->has('HTTP_TRUE_CLIENT_IP') &&
                    !empty($mainRequest->server->get('HTTP_TRUE_CLIENT_IP'))) {
                    $ip = $mainRequest->server->get('HTTP_TRUE_CLIENT_IP');
                } elseif ($mainRequest->server->has('HTTP_X_FORWARDED_FOR') &&
                    !empty($mainRequest->server->get('HTTP_X_FORWARDED_FOR'))) {
                    $ip = $mainRequest->server->get('HTTP_X_FORWARDED_FOR');
                } else {
                    $ip = $mainRequest->server->get('REMOTE_ADDR');
                }

                $record = $reader->city($ip);
                $country = $record->country->isoCode;
            } catch (\Exception $e) {
                // fail silently.
            }
        }

        if (!empty($country)) {
            $userCountry = strtoupper($country);
        }

        return $userCountry;
    }
}
