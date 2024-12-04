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

namespace I18nBundle\DataCollector;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use Pimcore\Http\RequestHelper;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class I18nDataCollector extends AbstractDataCollector
{
    protected I18nContextResolverInterface $i18nContextResolver;
    protected RequestHelper $requestHelper;
    protected bool $isFrontend = true;

    public function __construct(I18nContextResolverInterface $i18nContextResolver, RequestHelper $requestHelper)
    {
        $this->i18nContextResolver = $i18nContextResolver;
        $this->requestHelper = $requestHelper;

        $this->data = [
            'isFrontend' => false
        ];
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        //only track current valid routes.
        if ($response->getStatusCode() !== 200) {
            return;
        }

        if ($exception instanceof \RuntimeException
            || $this->requestHelper->isFrontendRequest($request) === false
            || $this->requestHelper->isFrontendRequestByAdmin($request)
        ) {
            return;
        }

        $i18nContext = $this->i18nContextResolver->getContext($request);
        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        $zone = $i18nContext->getZone();
        $zoneId = $zone->getId();

        $currentLocale = '--';
        $currentLanguage = '--';
        $currentCountry = '--';

        if ($i18nContext->getLocaleDefinition()->hasLocale()) {
            $currentLocale = $i18nContext->getLocaleDefinition()->getLocale();
        }

        if ($i18nContext->getLocaleDefinition()->hasCountryIso()) {
            $currentCountry = $i18nContext->getLocaleDefinition()->getCountryIso();
        }

        if ($i18nContext->getLocaleDefinition()->hasLanguageIso()) {
            $currentLanguage = $i18nContext->getLocaleDefinition()->getLanguageIso();
        }

        $this->data = [
            'isFrontend'      => true,
            'zoneId'          => $zoneId ?? 'None',
            'currentLocale'   => $currentLocale,
            'currentLanguage' => $currentLanguage,
            'currentCountry'  => $currentCountry
        ];
    }

    public static function getTemplate(): string
    {
        return '@I18n/profiler/data_collector.html.twig';
    }

    public function isFrontend(): bool
    {
        return $this->data['isFrontend'];
    }

    public function getName(): string
    {
        return 'i18n.data_collector';
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getLocale(): ?string
    {
        return $this->data['currentLocale'];
    }

    public function getLanguage(): ?string
    {
        return $this->data['currentLanguage'];
    }

    public function getCountry(): ?string
    {
        return $this->data['currentCountry'];
    }

    public function getZoneId(): string|int|null
    {
        return $this->data['zoneId'];
    }
}
