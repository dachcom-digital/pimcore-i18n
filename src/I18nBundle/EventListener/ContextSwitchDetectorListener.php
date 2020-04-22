<?php

namespace I18nBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\Document;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\I18nEvents;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Event\ContextSwitchEvent;
use I18nBundle\Manager\ZoneManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextSwitchDetectorListener implements EventSubscriberInterface
{
    /**
     * @var string|null
     */
    private $documentLocale;

    /**
     * @var string|null
     */
    private $documentLanguage;

    /**
     * @var string|null
     */
    private $documentCountry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var RequestValidatorHelper
     */
    protected $requestValidatorHelper;

    /**
     * @var array
     */
    protected $pimcoreConfig;

    /**
     * @param EventDispatcherInterface         $eventDispatcher
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     * @param ZoneManager                      $zoneManager
     * @param DocumentHelper                   $documentHelper
     * @param RequestValidatorHelper           $requestValidatorHelper
     * @param array                            $pimcoreConfig
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        ZoneManager $zoneManager,
        DocumentHelper $documentHelper,
        RequestValidatorHelper $requestValidatorHelper,
        array $pimcoreConfig
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->zoneManager = $zoneManager;
        $this->documentHelper = $documentHelper;
        $this->requestValidatorHelper = $requestValidatorHelper;
        $this->pimcoreConfig = $pimcoreConfig;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 0] // after i18n detector listener
            ]
        ];
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $fullPageEnabled = false;
        if (isset($this->pimcoreConfig['cache']) && isset($this->pimcoreConfig['cache']['enabled'])) {
            $fullPageEnabled = $this->pimcoreConfig['cache']['enabled'];
        } elseif (isset($this->pimcoreConfig['full_page_cache']) && isset($this->pimcoreConfig['full_page_cache']['enabled'])) {
            $fullPageEnabled = $this->pimcoreConfig['full_page_cache']['enabled'];
        }

        if ($fullPageEnabled === true) {
            return;
        }

        if ($event->isMasterRequest() === false) {
            return;
        }

        if ($this->requestValidatorHelper->matchesI18nContext($event->getRequest()) === false) {
            return;
        }

        $request = $event->getRequest();
        $document = $this->pimcoreDocumentResolver->getDocument($request);

        if (!$document instanceof Document) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request, false)) {
            return;
        }

        $this->setDocumentLocale($document);

        // check if zone, language or country has been changed,
        // trigger event for 3th party.
        $this->detectContextSwitch($request);

        $this->updateSessionData($request);
    }

    /**
     * Important: ContextSwitch only works in same domain levels.
     * Since there is no way for simple cross-domain session ids,
     * the zone switch has no relevance.
     *
     * @param Request $request
     *
     * @throws \Exception
     */
    private function detectContextSwitch(Request $request)
    {
        $session = $this->getSessionData($request);
        $currentZoneId = $this->zoneManager->getCurrentZoneInfo('zone_id');

        $localeHasSwitched = false;
        $languageHasSwitched = false;
        $countryHasSwitched = false;
        $zoneHasSwitched = false;

        if (is_null($session['lastLocale']) || (!is_null($session['lastLocale']) && $this->documentLocale !== $session['lastLocale'])) {
            $localeHasSwitched = true;
        }

        if (is_null($session['lastLanguage']) || (!is_null($session['lastLanguage']) && $this->documentLanguage !== $session['lastLanguage'])) {
            $languageHasSwitched = true;
        }

        if ($session['lastCountry'] !== false && (!is_null($session['lastCountry']) && $this->documentCountry !== $session['lastCountry'])) {
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
                'localeTo'            => $this->documentLocale,
                'languageHasSwitched' => $languageHasSwitched,
                'languageFrom'        => $session['lastLanguage'],
                'languageTo'          => $this->documentLanguage,
                'countryHasSwitched'  => $countryHasSwitched,
                'countryFrom'         => $session['lastCountry'],
                'countryTo'           => $this->documentCountry
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
                        $this->documentLocale,
                        $request->getRequestUri()
                    )
                );
            }

            if ($languageHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch language: from %s to %s. triggered by: %s',
                        $session['lastLanguage'],
                        $this->documentLanguage,
                        $request->getRequestUri()
                    )
                );
            }

            if ($countryHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch country: from %s to %s. triggered by: %s',
                        $session['lastCountry'],
                        $this->documentCountry,
                        $request->getRequestUri()
                    )
                );
            }

            $this->eventDispatcher->dispatch(I18nEvents::CONTEXT_SWITCH, new ContextSwitchEvent($params));
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getSessionData(Request $request)
    {
        /** @var NamespacedAttributeBag $bag */
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
     * @param Request $request
     *
     * @throws \Exception
     */
    protected function updateSessionData(Request $request)
    {
        $currentZoneId = $this->zoneManager->getCurrentZoneInfo('zone_id');

        /** @var NamespacedAttributeBag $bag */
        $bag = $request->getSession()->getBag('i18n_session');

        if (!empty($this->documentLocale)) {
            $bag->set('lastLocale', $this->documentLocale);
        }

        if (!empty($this->documentLanguage)) {
            $bag->set('lastLanguage', $this->documentLanguage);
        }

        if (!empty($this->documentCountry)) {
            $bag->set('lastCountry', $this->documentCountry);
        }

        $bag->set('lastZoneId', $currentZoneId);
    }

    /**
     * @param Document $document
     *
     * @throws \Exception
     */
    protected function setDocumentLocale(Document $document)
    {
        $i18nType = $this->zoneManager->getCurrentZoneInfo('mode');
        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($document, $i18nType);

        $this->documentLocale = $documentLocaleData['documentLocale'];
        $this->documentLanguage = $documentLocaleData['documentLanguage'];
        $this->documentCountry = $documentLocaleData['documentCountry'];
    }
}
