<?php

namespace I18nBundle\DataCollector;

use I18nBundle\Manager\ZoneManager;
use Pimcore\Cache\Runtime;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class I18nDataCollector extends DataCollector
{
    protected ZoneManager $zoneManager;
    protected RequestHelper $requestHelper;
    protected bool $isFrontend = true;

    public function __construct(ZoneManager $zoneManager, RequestHelper $requestHelper)
    {
        $this->zoneManager = $zoneManager;
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

        $zoneId = $this->zoneManager->getCurrentZoneInfo('zone_id');
        $mode = $this->zoneManager->getCurrentZoneInfo('mode');

        $currentLanguage = '--';
        $currentCountry = '--';

        if (Runtime::isRegistered('i18n.countryIso')) {
            $currentCountry = Runtime::get('i18n.countryIso');
        }

        if (Runtime::isRegistered('i18n.languageIso')) {
            $currentLanguage = Runtime::get('i18n.languageIso');
        }

        $this->data = [
            'isFrontend'      => true,
            'zoneId'          => empty($zoneId) ? 'none' : $zoneId,
            'i18nMode'        => $mode,
            'currentLanguage' => $currentLanguage,
            'currentCountry'  => $currentCountry
        ];
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

    public function getLanguage(): ?string
    {
        return $this->data['currentLanguage'];
    }

    public function getCountry(): ?string
    {
        return $this->data['currentCountry'];
    }

    public function getZoneId(): ?string
    {
        return $this->data['zoneId'];
    }
}
