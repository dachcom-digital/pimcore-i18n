<?php

namespace I18nBundle\DataCollector;

use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Model\I18nZoneInterface;
use Pimcore\Http\RequestHelper;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class I18nDataCollector extends AbstractDataCollector
{
    protected ZoneResolverInterface $zoneResolver;
    protected RequestHelper $requestHelper;
    protected bool $isFrontend = true;

    public function __construct(ZoneResolverInterface $zoneResolver, RequestHelper $requestHelper)
    {
        $this->zoneResolver = $zoneResolver;
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

        $zone = $this->zoneResolver->getZone($request);
        if (!$zone instanceof I18nZoneInterface) {
            return;
        }

        $zoneId = $zone->getZoneId();
        $mode = $zone->getMode();

        $currentLocale = '--';
        $currentLanguage = '--';
        $currentCountry = '--';

        if ($zone->getContext()->hasLocale()) {
            $currentLocale = $zone->getContext()->getLocale();
        }

        if ($zone->getContext()->hasCountryIso()) {
            $currentCountry = $zone->getContext()->getCountryIso();
        }

        if ($zone->getContext()->hasLanguageIso()) {
            $currentLanguage = $zone->getContext()->getLanguageIso();
        }

        $this->data = [
            'isFrontend'      => true,
            'zoneId'          => $zoneId ?? 'None',
            'i18nMode'        => $mode,
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

    public function getI18nMode(): ?string
    {
        return $this->data['i18nMode'];
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
