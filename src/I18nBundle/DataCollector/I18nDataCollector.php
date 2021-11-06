<?php

namespace I18nBundle\DataCollector;

use I18nBundle\Http\RouteItemResolverInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Http\RequestHelper;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class I18nDataCollector extends AbstractDataCollector
{
    protected RouteItemResolverInterface $routeItemResolver;
    protected RequestHelper $requestHelper;
    protected bool $isFrontend = true;

    public function __construct(RouteItemResolverInterface $routeItemResolver, RequestHelper $requestHelper)
    {
        $this->routeItemResolver = $routeItemResolver;
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

        $routeItem = $this->routeItemResolver->getRouteItem($request);
        if (!$routeItem instanceof RouteItemInterface) {
            return;
        }

        $zone = $routeItem->getI18nZone();
        $zoneId = $zone->getZoneId();
        $mode = $zone->getMode();

        $currentLocale = '--';
        $currentLanguage = '--';
        $currentCountry = '--';

        if ($routeItem->getLocaleDefinition()->hasLocale()) {
            $currentLocale = $routeItem->getLocaleDefinition()->getLocale();
        }

        if ($routeItem->getLocaleDefinition()->hasCountryIso()) {
            $currentCountry = $routeItem->getLocaleDefinition()->getCountryIso();
        }

        if ($routeItem->getLocaleDefinition()->hasLanguageIso()) {
            $currentLanguage = $routeItem->getLocaleDefinition()->getLanguageIso();
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
