<?php

namespace I18nBundle\EventListener;

use I18nBundle\Helper\CookieHelper;
use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Logger;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Adapter\Redirector\RedirectorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Event\ContextSwitchEvent;
use I18nBundle\I18nEvents;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Registry\RedirectorRegistry;
use I18nBundle\Tool\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Symfony\Component\Templating\EngineInterface;

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
    private $defaultLocale = null;

    /**
     * @var array
     */
    private $validLocales = [];

    /**
     * @var \Pimcore\Model\Document
     */
    private $document = null;

    /**
     * @var null
     */
    private $documentLocale = null;

    /**
     * @var null
     */
    private $documentLanguage = null;

    /**
     * @var null
     */
    private $documentCountry = null;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var RedirectorRegistry
     */
    protected $redirectorRegistry;

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
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var Request
     */
    protected $request;

    /**
     * DetectorListener constructor.
     *
     * @param EngineInterface      $templating
     * @param Configuration        $configuration
     * @param CookieHelper         $cookieHelper
     * @param RedirectorRegistry   $redirectorRegistry
     * @param DocumentResolver     $documentResolver
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param EditmodeResolver     $editmodeResolver
     */
    public function __construct(
        EngineInterface $templating,
        Configuration $configuration,
        CookieHelper $cookieHelper,
        RedirectorRegistry $redirectorRegistry,
        DocumentResolver $documentResolver,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        EditmodeResolver $editmodeResolver
    ) {
        $this->templating = $templating;
        $this->configuration = $configuration;
        $this->cookieHelper = $cookieHelper;
        $this->redirectorRegistry = $redirectorRegistry;
        $this->documentResolver = $documentResolver;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->editmodeResolver = $editmodeResolver;
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
            ],
            KernelEvents::RESPONSE  => 'onKernelResponse'
        ];
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     */
    private function initI18nSystem($request)
    {
        //initialize all managers!
        $this->zoneManager->initZones();

        $document = null;
        if ($this->document instanceof Document) {
            $document = $this->document;
            if ($this->document instanceof Document\Hardlink\Wrapper) {
                $document = $this->document->getHardLinkSource();
            }
        }

        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'), $document);
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->isMasterRequest() === false) {
            return;
        }

        $this->initI18nSystem($event->getRequest());
        $this->document = $this->documentResolver->getDocument($this->request);

        $locale = $event->getRequest()->getLocale();

        $languageIso = $locale;
        if (strpos($locale, '_') !== false) {
            $localeFragments = explode('_', $locale);
            $languageIso = $localeFragments[0];
        }

        //fallback.
        Cache\Runtime::set('i18n.locale', $event->getRequest()->getLocale());
        Cache\Runtime::set('i18n.languageIso', strtolower($languageIso));
        Cache\Runtime::set('i18n.countryIso', Definitions::INTERNATIONAL_COUNTRY_NAMESPACE);
    }

    /**
     * If we're in static route context, we need to check the request locale since it could be a invalid one from the url (like
     * en-us). Always use the document locale then!
     *
     * Since symfony tries to locate the current locale in LocaleListener via the request attribute "_locale", we need to trigger
     * this event earlier!
     *
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequestLocale(GetResponseEvent $event)
    {
        if ($this->setValidRequest($event) === false) {
            return;
        }

        $this->setDocumentLocale();

        $requestSource = $this->request->attributes->get('pimcore_request_source');
        if ($requestSource === 'staticroute' && !empty($this->documentLocale) && $this->request->attributes->get('_locale') !== $this->documentLocale) {
            $this->adjustRequestLocale();
        }
    }

    /**
     * Apply this method after the pimcore context resolver.
     *
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->setValidRequest($event) === false) {
            return;
        }

        $this->initI18nSystem($this->request);

        $currentRouteName = $this->request->get('_route');
        $requestSource = $this->request->attributes->get('pimcore_request_source');

        $this->i18nType = $this->zoneManager->getCurrentZoneInfo('mode');

        $this->setDocumentLocale();

        $this->validLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
        $this->defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();

        /**
         * If a root node hardlink is requested e.g. /en-us, pimcore gets the locale from the source, which is "quite" wrong.
         */
        $requestLocale = $this->request->getLocale();
        if ($this->document instanceof Document\Hardlink\Wrapper) {
            if (!empty($this->documentLocale) && $this->documentLocale !== $requestLocale) {
                $this->adjustRequestLocale();
            }
        }

        $validRoute = false;
        if ($requestSource === 'staticroute' || $currentRouteName === 'document_' . $this->document->getId()) {
            $validRoute = true;
        }

        if ($validRoute === true && empty($this->documentLocale)) {
            $this->setNotEditableAwareMessage($event);
        }

        $validLocale = !empty($this->documentLocale) && array_search($this->documentLocale, array_column($this->validLocales, 'locale')) !== false;

        // @todo:
        // currently, redirect works only with pimcore documents and static routes.
        // symfony routes will be ignored.
        if ($validRoute) {
            $redirectUrl = false;
            $validForRedirect = $validLocale === false;

            if ($this->canRedirect() && $validForRedirect === true) {
                $options = [
                    'i18nType'        => $this->i18nType,
                    'request'         => $this->request,
                    'document'        => $this->document,
                    'documentLocale'  => $this->documentLocale,
                    'documentCountry' => $this->documentCountry,
                    'defaultLocale'   => $this->defaultLocale
                ];

                $redirectorBag = new RedirectorBag($options);

                /** @var RedirectorInterface $redirector */
                foreach ($this->redirectorRegistry->all() as $redirector) {
                    $redirector->makeDecision($redirectorBag);
                    $decision = $redirector->getDecision();

                    if ($decision['valid'] === true) {
                        $redirectUrl = $decision['url'];
                    }

                    $redirectorBag->addRedirectorDecisionToBag($redirector->getName(), $decision);
                }
            }

            if ($redirectUrl !== false) {
                $event->setResponse(new RedirectResponse($this->getRedirectUrl($redirectUrl)));

                return;
            }
        }

        //Set Locale.
        if ($validLocale === true) {
            Cache\Runtime::set('i18n.locale', $this->documentLocale);
            Cache\Runtime::set('i18n.languageIso', $this->documentLanguage);
        }

        //Set Country. This variable is only !false if i18n country is active
        if (!empty($this->documentCountry)) {
            Cache\Runtime::set('i18n.countryIso', $this->documentCountry);
        }

        //check if zone, language or country has been changed, trigger event for 3th party.
        $this->detectContextSwitch();

        //update session
        $this->updateSessionData();
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->isMasterRequest() === false) {
            return;
        }

        if ($event->getRequest()->getPathInfo() === '/') {
            return;
        }

        $currentLocale = false;
        $currentLanguage = false;
        $currentCountry = false;

        $registryConfig = $this->configuration->getConfig('registry');
        $available = isset($registryConfig['redirector']['cookie'])
            ? $registryConfig['redirector']['cookie']['enabled'] : true;

        //check if we're allowed to bake a cookie at the first place!
        if ($available === false) {
            return;
        }

        if (Cache\Runtime::isRegistered('i18n.locale')) {
            $currentLocale = Cache\Runtime::get('i18n.locale');
        }

        if (Cache\Runtime::isRegistered('i18n.languageIso')) {
            $currentLanguage = Cache\Runtime::get('i18n.languageIso');
        }

        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            $currentCountry = Cache\Runtime::get('i18n.countryIso');
        }

        $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);
        $validUri = $this->getRedirectUrl(strtok($event->getRequest()->getUri(), '?'));

        $cookie = $this->cookieHelper->get($event->getRequest());

        //same domain, do nothing.
        if ($cookie !== false && $validUri === $cookie['url']) {
            return;
        }

        //check if url is valid
        $indexId = array_search($validUri, array_column($zoneDomains, 'url'));
        if ($indexId !== false) {
            $cookieData = [
                'url'      => $validUri,
                'locale'   => $currentLocale,
                'language' => $currentLanguage,
                'country'  => $currentCountry
            ];

            $cookie = $this->cookieHelper->set($event->getResponse(), $cookieData);
        }
    }

    /**
     * Important: ContextSwitch only works in same domain levels.
     * Since there is no way for simple cross-domain session ids,
     * the zone switch will be sort of useless most of the time. :(.
     */
    private function detectContextSwitch()
    {
        if (!$this->isValidI18nCheckRequest($this->request)) {
            return;
        }

        $session = $this->getSessionData();
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
                        $this->request->getRequestUri()
                    )
                );
            }

            if ($localeHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch locale: from %s to %s. triggered by: %s',
                        $session['lastLocale'],
                        $this->documentLocale,
                        $this->request->getRequestUri()
                    )
                );
            }

            if ($languageHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch language: from %s to %s. triggered by: %s',
                        $session['lastLanguage'],
                        $this->documentLanguage,
                        $this->request->getRequestUri()
                    )
                );
            }

            if ($countryHasSwitched === true) {
                Logger::log(
                    sprintf(
                        'switch country: from %s to %s. triggered by: %s',
                        $session['lastCountry'],
                        $this->documentCountry,
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
     * @return array
     */
    private function getSessionData()
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

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

    private function updateSessionData()
    {
        if (!$this->isValidI18nCheckRequest($this->request)) {
            return;
        }

        $currentZoneId = $this->zoneManager->getCurrentZoneInfo('zone_id');

        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

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
     * @param string $path
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
     * @param Request $request
     * @param bool    $allowAjax
     *
     * @return bool
     */
    private function isValidI18nCheckRequest(Request $request, $allowAjax = false)
    {
        if (\Pimcore\Tool::isFrontendRequestByAdmin($request)) {
            return false;
        }

        if (System::isInCliMode() || ($allowAjax === false && $request->isXmlHttpRequest())) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
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
            return true;
        }

        if ($event->isMasterRequest() === false) {
            return false;
        }

        $this->request = $event->getRequest();
        if (!$this->matchesPimcoreContext($this->request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        $this->document = $this->documentResolver->getDocument($this->request);
        if (!$this->document) {
            return false;
        }

        if (!$this->isValidI18nCheckRequest($this->request, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    private function setNotEditableAwareMessage(GetResponseEvent $event)
    {
        //if document is root, no language tag is required
        if ($this->editmodeResolver->isEditmode()) {
            $response = new Response();
            $language = 'en';
            if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                $language = $user->getLanguage();
            } elseif ($user = \Pimcore\Tool\Authentication::authenticateSession()) {
                $language = $user->getLanguage();
            }

            $response->setContent($this->templating->render('I18nBundle::not_editable_aware_message.html.twig', ['adminLocale' => $language]));
            $event->setResponse($response);

            return;
        } else {
            $siteId = 1;
            if (\Pimcore\Model\Site::isSiteRequest() === true) {
                $site = \Pimcore\Model\Site::getCurrentSite();
                $siteId = $site->getRootId();
            }

            //if document is root, no language tag is required
            if ($this->document->getId() !== $siteId) {
                throw new \Exception(get_class($this->document) . ' (' . $this->document->getId() . ') does not have a valid language property!');
            }
        }
    }

    private function setDocumentLocale()
    {
        if ($this->document instanceof Document\Hardlink\Wrapper) {
            $this->documentLocale = $this->document->getHardLinkSource()->getProperty('language');
        } else {
            $this->documentLocale = $this->document->getProperty('language');
        }

        if ($this->i18nType === 'country') {
            $this->documentCountry = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        $this->documentLanguage = $this->documentLocale;

        if (strpos($this->documentLocale, '_') !== false) {
            $parts = explode('_', $this->documentLocale);
            $this->documentLanguage = strtolower($parts[0]);
            if (isset($parts[1]) && !empty($parts[1])) {
                $this->documentCountry = strtoupper($parts[1]);
            }
        }
    }

    /**
     * Adjust Request locale.
     */
    private function adjustRequestLocale()
    {
        // set request locale
        $this->request->attributes->set('_locale', $this->documentLocale);
        $this->request->setLocale($this->documentLocale);

        //set route param locale
        $routeParams = $this->request->attributes->get('_route_params');
        if (!is_array($routeParams)) {
            $routeParams = [];
        }
        $routeParams['_locale'] = $this->documentLocale;
        $this->request->attributes->set('_route_params', $routeParams);
    }
}
