<?php

namespace I18nBundle\EventListener;

use I18nBundle\Definitions;
use I18nBundle\Event\ContextSwitchEvent;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\UserHelper;
use I18nBundle\Helper\ZoneHelper;
use I18nBundle\I18nEvents;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Tool\System;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Pimcore\Cache;
use Pimcore\Logger;

use Pimcore\Model\Document;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;

class DetectorListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var string
     */
    private $i18nType = 'language';

    /**
     * @var string
     */
    private $defaultLanguage = NULL;

    /**
     * @var string
     */
    private $defaultCountry = NULL;

    /**
     * @var array
     */
    private $validLanguages = [];

    /**
     * @var array
     */
    private $validCountries = [];

    /**
     * @var \Pimcore\Model\Document
     */
    private $document = NULL;

    /**
     * @var null
     */
    private $documentLanguage = NULL;

    /**
     * @var null
     */
    private $documentCountry = NULL;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * @var PathGeneratorManager
     */
    protected $pathGeneratorManager;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var ZoneHelper
     */
    protected $zoneHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * DetectorListener constructor.
     *
     * @param DocumentResolver     $documentResolver
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param DocumentHelper       $documentHelper
     * @param ZoneHelper           $zoneHelper
     * @param UserHelper           $userHelper
     */
    public function __construct(
        DocumentResolver $documentResolver,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        DocumentHelper $documentHelper,
        ZoneHelper $zoneHelper,
        UserHelper $userHelper
    ) {
        $this->documentResolver = $documentResolver;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->documentHelper = $documentHelper;
        $this->zoneHelper = $zoneHelper;
        $this->userHelper = $userHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 20],       //before responseExceptionListener
            KernelEvents::REQUEST   => [
                ['onKernelRequestLocale', 17],                          // before symfony LocaleListener
                ['onKernelRequest', 2]                                  // after pimcore context resolver
            ]
        ];
    }

    private function initI18nSystem($request)
    {
        //initialize all managers!
        $this->zoneManager->initZones();
        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'));
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->isMasterRequest() === FALSE) {
            return;
        }

        $this->initI18nSystem($event->getRequest());
        $this->document = $this->documentResolver->getDocument($this->request);

        //fallback.
        Cache\Runtime::set('i18n.languageIso', strtolower($event->getRequest()->getLocale()));
        Cache\Runtime::set('i18n.countryIso', Definitions::INTERNATIONAL_COUNTRY_NAMESPACE);
    }

    /**
     * If we're in static route context, we need to check the request locale since it could be a invalid one from the url (like en-us).
     * Always use the document locale then!
     *
     * Since symfony tries to locate the current locale in LocaleListener via the request attribute "_locale", we need to trigger this event earlier!
     *
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequestLocale(GetResponseEvent $event)
    {
        if ($this->setValidRequest($event) === FALSE) {
            return;
        }

        $requestSource = $this->request->attributes->get('pimcore_request_source');
        if ($requestSource === 'staticroute' && !empty($this->documentLanguage) && $this->request->attributes->get('_locale') !== $this->documentLanguage) {
            $this->adjustRequestLocale();
        }
    }

    /**
     * Apply this method after the pimcore context resolver
     *
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->setValidRequest($event) === FALSE) {
            return;
        }

        $this->initI18nSystem($this->request);

        $currentRouteName = $this->request->get('_route');
        $requestSource = $this->request->attributes->get('pimcore_request_source');

        $this->i18nType = $this->zoneManager->getCurrentZoneInfo('mode');
        $this->validLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getActiveLanguages();
        $this->defaultLanguage = $this->zoneManager->getCurrentZoneLanguageAdapter()->getDefaultLanguage();

        if ($this->i18nType === 'country') {
            $this->validCountries = $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
            $this->defaultCountry = $this->zoneManager->getCurrentZoneCountryAdapter()->getDefaultCountry();
        }

        /**
         * If a root node hardlink is requested e.g. /en-us, pimcore gets the locale from the source, which is "quite" wrong.
         */
        $requestLocale = $this->request->getLocale();
        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            if (!empty($this->documentLanguage) && $this->documentLanguage !== $requestLocale) {
                $this->adjustRequestLocale();
            }
        }

        $validRoute = FALSE;
        if ($requestSource === 'staticroute' || $currentRouteName === 'document_' . $this->document->getId()) {
            $validRoute = TRUE;
        }

        if ($validRoute === TRUE && empty($this->documentLanguage)) {

            $siteId = 1;
            if (\Pimcore\Model\Site::isSiteRequest() === TRUE) {
                $site = \Pimcore\Model\Site::getCurrentSite();
                $siteId = $site->getRootId();
            }

            //if document is root, no language tag is required
            if ($this->document->getId() !== $siteId) {
                throw new \Exception(get_class($this->document) . ' (' . $this->document->getId() . ') does not have a valid language property!');
            }
        }

        $currentCountry = FALSE;
        $currentLanguage = FALSE;

        $validCountry = !empty($this->documentCountry) && array_search(strtoupper($this->documentCountry), array_column($this->validCountries, 'isoCode')) !== FALSE;
        $validLanguage = !empty($this->documentLanguage) && array_search($this->documentLanguage, array_column($this->validLanguages, 'isoCode')) !== FALSE;

        // @todo: currently, redirect works only with pimcore documents and static routes. symfony routes will be ignored.
        if ($validRoute) {
            if ($this->i18nType === 'language') {
                //first get valid language
                if (!$validLanguage) {
                    if ($this->canRedirect() && $this->i18nType === 'language') {
                        $url = $this->getRedirectUrl($this->getLanguageUrl());
                        $event->setResponse(new RedirectResponse($url));
                        return;
                    }
                }
            } elseif ($this->i18nType === 'country') {
                //we are wrong. redirect user!
                if ($this->canRedirect() && (!$validCountry || !$validLanguage)) {
                    $url = $this->getRedirectUrl($this->getCountryUrl());
                    $event->setResponse(new RedirectResponse($url));
                    return;
                }
            }
        }

        //Set Locale.
        if ($validLanguage === TRUE) {
            if (strpos($this->documentLanguage, '_') !== FALSE) {
                $parts = explode('_', $this->documentLanguage);
                $currentLanguage = $parts[0];
            } else {
                $currentLanguage = $this->documentLanguage;
            }

            Cache\Runtime::set('i18n.languageIso', strtolower($currentLanguage));
        }

        //Set Country. This variable is only !false if i18n country is active
        if ($validCountry === TRUE) {
            $currentCountry = strtoupper($this->documentCountry);
            Cache\Runtime::set('i18n.countryIso', $currentCountry);
        }

        $currentZoneId = $this->zoneManager->getCurrentZoneInfo('zoneId');

        //check if zone, language or country has been changed, trigger event for 3th party.
        $this->detectContextSwitch($currentZoneId, $currentLanguage, $currentCountry);

        //update session
        $this->updateSessionData($currentZoneId, $currentLanguage, $currentCountry);
    }

    /**
     * Important: ContextSwitch only works in same domain levels.
     * Since there is no way for simple cross-domain session ids, the zone switch will be sort of useless most of the time. :(
     *
     * @param $currentZoneId
     * @param $currentLanguage
     * @param $currentCountry
     *
     * @return void
     */
    private function detectContextSwitch($currentZoneId, $currentLanguage, $currentCountry)
    {
        if (!$this->isValidI18nCheckRequest()) {
            return;
        }

        $session = $this->getSessionData();

        $languageHasSwitched = FALSE;
        $countryHasSwitched = FALSE;
        $zoneHasSwitched = FALSE;

        if (is_null($session['lastLanguage']) || (!is_null($session['lastLanguage']) && $currentLanguage !== $session['lastLanguage'])) {
            $languageHasSwitched = TRUE;
        }

        if ($session['lastCountry'] !== FALSE && (!is_null($session['lastCountry']) && $currentCountry !== $session['lastCountry'])) {
            $countryHasSwitched = TRUE;
        }

        if ($currentZoneId !== $session['lastZoneId']) {
            $zoneHasSwitched = TRUE;
        }

        if ($zoneHasSwitched || $languageHasSwitched || $countryHasSwitched) {

            $params = [
                'zoneHasSwitched'     => $zoneHasSwitched,
                'zoneFrom'            => $session['lastZoneId'],
                'zoneTo'              => $currentZoneId,
                'languageHasSwitched' => $languageHasSwitched,
                'languageFrom'        => $session['lastLanguage'],
                'languageTo'          => $currentLanguage,
                'countryHasSwitched'  => $countryHasSwitched,
                'countryFrom'         => $session['lastCountry'],
                'countryTo'           => $currentCountry
            ];

            if ($zoneHasSwitched === TRUE) {
                Logger::log(
                    sprintf(
                        'switch zone: from %s to %s. triggered by: %s',
                        $session['lastZoneId'],
                        $currentZoneId,
                        $this->request->getRequestUri()
                    )
                );
            }

            if ($languageHasSwitched === TRUE) {
                Logger::log(
                    sprintf(
                        'switch language: from %s to %s. triggered by: %s',
                        $session['lastLanguage'],
                        $currentLanguage,
                        $this->request->getRequestUri()
                    )
                );
            }

            if ($countryHasSwitched === TRUE) {
                Logger::log(
                    sprintf(
                        'switch country: from %s to %s. triggered by: %s',
                        $session['lastCountry'],
                        $currentCountry,
                        $this->request->getRequestUri()
                    )
                );
            }

            \Pimcore::getEventDispatcher()->dispatch(
                I18nEvents::CONTEXT_SWITCH,
                new ContextSwitchEvent($params)
            );
        }
    }

    /**
     * Returns absolute Url to website with language-country context.
     * Because this could be a different domain, absolute url is necessary
     *
     * @return bool|string
     */
    private function getCountryUrl()
    {
        $userLanguageIso = $this->userHelper->guessLanguage($this->validLanguages);
        $userCountryIso = $this->userHelper->guessCountry($this->validCountries);

        $matchUrl = $this->zoneHelper->findUrlInZoneTree(
            $this->zoneManager->getCurrentZoneDomains(TRUE),
            $userLanguageIso,
            $this->defaultLanguage,
            $userCountryIso,
            $this->defaultCountry
        );

        return $matchUrl;
    }

    /**
     * Returns absolute Url to website with language context.
     * Because this could be a different domain, absolute url is necessary
     *
     * @return bool|string
     */
    private function getLanguageUrl()
    {
        $userLanguageIso = $this->userHelper->guessLanguage($this->validLanguages);
        $defaultLanguageIso = $this->defaultLanguage;

        $matchUrl = $this->zoneHelper->findUrlInZoneTree(
            $this->zoneManager->getCurrentZoneDomains(TRUE),
            $userLanguageIso,
            $defaultLanguageIso
        );

        return $matchUrl;
    }

    /**
     * @return array
     */
    private function getSessionData()
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

        $data = [
            'lastLanguage' => NULL,
            'lastCountry'  => NULL,
            'lastZoneId'   => NULL
        ];

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
     * @param null|int $currentZoneId
     * @param bool     $languageData
     * @param bool     $countryData
     *
     * @return void
     */
    private function updateSessionData($currentZoneId = NULL, $languageData = FALSE, $countryData = FALSE)
    {
        if (!$this->isValidI18nCheckRequest()) {
            return;
        }

        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

        if ($languageData !== FALSE) {
            $bag->set('lastLanguage', $languageData);
        }

        if ($countryData !== FALSE) {
            $bag->set('lastCountry', $countryData);
        }

        $bag->set('lastZoneId', $currentZoneId);
    }

    /**
     * @param $path
     *
     * @return string
     */
    private function getRedirectUrl($path)
    {
        $config = \Pimcore\Config::getSystemConfig();

        $endPath = rtrim($path, '/');

        if ($config->documents->allowtrailingslash !== 'no') {
            $endPath = $endPath . '/';
        }

        return $endPath;
    }

    /**
     * @param bool $allowAjax
     *
     * @return bool
     */
    private function isValidI18nCheckRequest($allowAjax = FALSE)
    {
        if (System::isInCliMode() || ($allowAjax === FALSE && $this->request->isXmlHttpRequest())) {
            return FALSE;
        }

        return TRUE;
    }

    private function canRedirect()
    {
        return !System::isInBackend($this->request);
    }

    /**
     * @param GetResponseEvent $event
     *
     * @return bool
     */
    private function setValidRequest(GetResponseEvent $event)
    {
        // already initialized.
        if ($this->document instanceof Document) {
            return TRUE;
        }

        if ($event->isMasterRequest() === FALSE) {
            return FALSE;
        }

        $this->request = $event->getRequest();
        if (!$this->matchesPimcoreContext($this->request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return FALSE;
        }

        $this->document = $this->documentResolver->getDocument($this->request);
        if (!$this->document) {
            return FALSE;
        }

        if (!$this->isValidI18nCheckRequest(TRUE)) {
            return FALSE;
        }

        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $this->documentCountry = $this->document->getHardLinkSource()->getProperty('country');
            $this->documentLanguage = $this->document->getHardLinkSource()->getProperty('language');
        } else {
            $this->documentCountry = $this->document->getProperty('country');
            $this->documentLanguage = $this->document->getProperty('language');
        }

        return TRUE;
    }

    /**
     * Adjust Request locale
     */
    private function adjustRequestLocale()
    {
        // set request locale
        $this->request->attributes->set('_locale', $this->documentLanguage);
        $this->request->setLocale($this->documentLanguage);

        //set route param locale
        $routeParams = $this->request->attributes->get('_route_params');
        if (is_array($routeParams)) {
            $routeParams = [];
        }
        $routeParams['_locale'] = $this->documentLanguage;
        $this->request->attributes->set('_route_params', $routeParams);
    }
}