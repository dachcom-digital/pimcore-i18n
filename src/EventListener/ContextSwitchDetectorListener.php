<?php

namespace I18nBundle\EventListener;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model\Document;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\I18nEvents;
use I18nBundle\Event\ContextSwitchEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextSwitchDetectorListener implements EventSubscriberInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        protected I18nContextResolverInterface $i18nContextResolver,
        protected RequestValidatorHelper $requestValidatorHelper,
        protected Config $pimcoreConfig,
        protected Configuration $configuration
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 0] // after i18n detector listener
            ]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $fullPageEnabled = false;
        $request = $event->getRequest();

        if (isset($this->pimcoreConfig['full_page_cache']['enabled'])) {
            $fullPageEnabled = $this->pimcoreConfig['full_page_cache']['enabled'];
        }

        if ($this->configuration->getConfig('enable_context_switch_detector') === false) {
            return;
        }

        if ($fullPageEnabled === true) {
            return;
        }

        if ($event->isMainRequest() === false) {
            return;
        }

        if ($this->requestValidatorHelper->matchesI18nContext($event->getRequest()) === false) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request, false)) {
            return;
        }

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        if (!$document instanceof Document) {
            return;
        }

        $i18nContext = $this->i18nContextResolver->getContext($request);
        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        // check if zone, language or country has been changed,
        // trigger event for 3th party.
        $this->detectContextSwitch($i18nContext, $request);
        $this->updateSessionData($i18nContext, $request);
    }

    /**
     * Important: ContextSwitch only works in same domain levels.
     * Since there is no way for simple cross-domain session ids,
     * the zone switch has no relevance.
     *
     * @throws \Exception
     */
    private function detectContextSwitch(I18nContextInterface $i18nContext, Request $request): void
    {
        $zone = $i18nContext->getZone();

        $session = $this->getSessionData($request);
        $currentZoneId = $zone->getId();

        $localeHasSwitched = false;
        $languageHasSwitched = false;
        $countryHasSwitched = false;
        $zoneHasSwitched = false;

        $documentLocale = $i18nContext->getLocaleDefinition()->getLocale();
        $documentLanguage = $i18nContext->getLocaleDefinition()->getLanguageIso();
        $documentCountry = $i18nContext->getLocaleDefinition()->getCountryIso();

        if (is_null($session['lastLocale']) || ($documentLocale !== $session['lastLocale'])) {
            $localeHasSwitched = true;
        }

        if (is_null($session['lastLanguage']) || ($documentLanguage !== $session['lastLanguage'])) {
            $languageHasSwitched = true;
        }

        if ($session['lastCountry'] !== false && (!is_null($session['lastCountry']) && $documentCountry !== $session['lastCountry'])) {
            $countryHasSwitched = true;
            $localeHasSwitched = true;
        }

        if ($currentZoneId !== $session['lastZoneId']) {
            $zoneHasSwitched = true;
        }

        if ($zoneHasSwitched || $localeHasSwitched || $languageHasSwitched || $countryHasSwitched) {
            $params = [
                'zoneHasSwitched'     => $zoneHasSwitched,
                'zoneFrom'            => $session['lastZoneId'],
                'zoneTo'              => $currentZoneId,
                'localeHasSwitched'   => $localeHasSwitched,
                'localeFrom'          => $session['lastLocale'],
                'localeTo'            => $documentLocale,
                'languageHasSwitched' => $languageHasSwitched,
                'languageFrom'        => $session['lastLanguage'],
                'languageTo'          => $documentLanguage,
                'countryHasSwitched'  => $countryHasSwitched,
                'countryFrom'         => $session['lastCountry'],
                'countryTo'           => $documentCountry
            ];

            if ($zoneHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch zone: from %s to %s. triggered by: %s',
                        $session['lastZoneId'],
                        $currentZoneId,
                        $request->getRequestUri()
                    )
                );
            }

            if ($localeHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch locale: from %s to %s. triggered by: %s',
                        $session['lastLocale'],
                        $documentLocale,
                        $request->getRequestUri()
                    )
                );
            }

            if ($languageHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch language: from %s to %s. triggered by: %s',
                        $session['lastLanguage'],
                        $documentLanguage,
                        $request->getRequestUri()
                    )
                );
            }

            if ($countryHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch country: from %s to %s. triggered by: %s',
                        $session['lastCountry'],
                        $documentCountry,
                        $request->getRequestUri()
                    )
                );
            }

            $this->eventDispatcher->dispatch(new ContextSwitchEvent($params), I18nEvents::CONTEXT_SWITCH);
        }
    }

    protected function getSessionData(Request $request): array
    {
        $bag = $request->getSession()->getBag('i18n_session');

        $data = [
            'lastLocale'   => null,
            'lastLanguage' => null,
            'lastCountry'  => null,
            'lastZoneId'   => null
        ];

        if ($bag->has('lastLocale')) {
            $data['lastLocale'] = $bag->get('lastLocale');
        }

        if ($bag->has('lastLanguage')) {
            $data['lastLanguage'] = $bag->get('lastLanguage');
        }

        if ($bag->get('lastCountry')) {
            $data['lastCountry'] = $bag->get('lastCountry');
        }

        //if no zone as been defined, zone id is always NULL.
        $data['lastZoneId'] = $bag->get('lastZoneId');

        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function updateSessionData(I18nContextInterface $i18nContext, Request $request): void
    {
        $documentLocale = $i18nContext->getLocaleDefinition()->getLocale();
        $documentLanguage = $i18nContext->getLocaleDefinition()->getLanguageIso();
        $documentCountry = $i18nContext->getLocaleDefinition()->getCountryIso();
        $currentZoneId = $i18nContext->getZone()->getId();

        $bag = $request->getSession()->getBag('i18n_session');

        if (!empty($documentLocale)) {
            $bag->set('lastLocale', $documentLocale);
        }

        if (!empty($documentLanguage)) {
            $bag->set('lastLanguage', $documentLanguage);
        }

        if (!empty($documentCountry)) {
            $bag->set('lastCountry', $documentCountry);
        }

        $bag->set('lastZoneId', $currentZoneId);
    }
}
