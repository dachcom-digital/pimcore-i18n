<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

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
        $mainRequest = $this->requestStack->getMainRequest();
        if (!$mainRequest instanceof Request) {
            return [];
        }

        $guessedLanguages = [];
        $acceptLanguages = $mainRequest->getLanguages();

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
