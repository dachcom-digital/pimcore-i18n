<?php

namespace I18nBundle\EventListener;

use I18nBundle\Helper\CookieHelper;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Adapter\Redirector\RedirectorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Registry\RedirectorRegistry;
use I18nBundle\Tool\System;
use Pimcore\Model\Site;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
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
use Symfony\Component\Templating\EngineInterface;

class DetectorListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $defaultLocale = null;

    /**
     * @var array
     */
    private $validLocales = [];

    /**
     * @var Document
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
     * @var Request
     */
    private $request;

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
     * @var DocumentResolver
     */
    protected $documentResolver;

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
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var RequestValidatorHelper
     */
    protected $requestValidatorHelper;

    /**
     * @param EngineInterface        $templating
     * @param Configuration          $configuration
     * @param CookieHelper           $cookieHelper
     * @param RedirectorRegistry     $redirectorRegistry
     * @param DocumentResolver       $documentResolver
     * @param ZoneManager            $zoneManager
     * @param ContextManager         $contextManager
     * @param PathGeneratorManager   $pathGeneratorManager
     * @param EditmodeResolver       $editmodeResolver
     * @param DocumentHelper         $documentHelper
     * @param RequestValidatorHelper $requestValidatorHelper
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
        EditmodeResolver $editmodeResolver,
        DocumentHelper $documentHelper,
        RequestValidatorHelper $requestValidatorHelper
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
        $this->documentHelper = $documentHelper;
        $this->requestValidatorHelper = $requestValidatorHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 20]           // before responseExceptionListener
            ],
            KernelEvents::REQUEST   => [
                ['onKernelRequestLocale', 17],      // before symfony LocaleListener
                ['onKernelRequest', 2]              // after pimcore context resolver
            ],
            KernelEvents::RESPONSE  => 'onKernelResponse'
        ];
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

        $locale = $event->getRequest()->getLocale();

        $languageIso = $locale;
        if (strpos($locale, '_') !== false) {
            $localeFragments = explode('_', $locale);
            $languageIso = $localeFragments[0];
        }

        //fallback.
        Cache\Runtime::set('i18n.locale', $locale);
        Cache\Runtime::set('i18n.languageIso', strtolower($languageIso));
        Cache\Runtime::set('i18n.countryIso', Definitions::INTERNATIONAL_COUNTRY_NAMESPACE);
    }

    /**
     * If we're in static route context, we need to check the request locale since it could be a invalid one from the url
     * (like en-us). Always use the document locale then!
     *
     * Since symfony tries to locate the current locale in LocaleListener via the request attribute "_locale",
     * we need to trigger his event earlier!
     *
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequestLocale(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() === false) {
            return;
        }

        $this->request = $event->getRequest();
        $this->document = $this->documentResolver->getDocument($this->request);

        if ($this->isValidRequest() === false) {
            return;
        }

        $this->setDocumentLocale('language');

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
        if ($event->isMasterRequest() === false) {
            return;
        }

        $this->request = $event->getRequest();
        $this->document = $this->documentResolver->getDocument($this->request);

        if ($this->isValidRequest() === false) {
            return;
        }

        $this->initI18nSystem($this->request);

        $currentRouteName = $this->request->get('_route');
        $requestSource = $this->request->attributes->get('pimcore_request_source');

        $this->validLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
        $this->defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();

        $i18nType = $this->zoneManager->getCurrentZoneInfo('mode');

        $this->setDocumentLocale($i18nType);

        /**
         * If a root node hardlink is requested e.g. /en-us, pimcore gets the locale from the source, which is "quite" wrong.
         */
        $requestLocale = $this->request->getLocale();
        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
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
                    'i18nType'        => $i18nType,
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
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->isMasterRequest() === false) {
            return;
        }

        if ($this->requestValidatorHelper->matchesDefaultPimcoreContext($event->getRequest()) === false) {
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
            ? $registryConfig['redirector']['cookie']['enabled']
            : true;

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

            $this->cookieHelper->set($event->getResponse(), $cookieData);
        }
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     */
    private function initI18nSystem($request)
    {
        $this->zoneManager->initZones();

        $document = null;
        if ($this->document instanceof Document) {
            $document = $this->document;
            if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                /** @var Document\Hardlink\Wrapper $wrapperDocument */
                $wrapperDocument = $this->document;
                $document = $wrapperDocument->getHardLinkSource();
            }
        }

        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'), $document);
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));
    }

    /**
     * @param string $path
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getRedirectUrl($path)
    {
        $config = \Pimcore\Config::getSystemConfig();

        $endPath = rtrim($path, '/');

        if ($config->documents->allowtrailingslash !== 'no') {
            $endPath = $endPath . '/';
        }

        return $endPath;
    }

    /**
     * @return bool
     */
    protected function canRedirect()
    {
        return !System::isInBackend($this->request);
    }

    /**
     * @return bool
     */
    protected function isValidRequest()
    {
        if (empty($this->document)) {
            return false;
        }

        return $this->requestValidatorHelper->isValidForRedirect($this->request);
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    protected function setNotEditableAwareMessage(GetResponseEvent $event)
    {
        //if document is root, no language tag is required
        if ($this->editmodeResolver->isEditmode()) {
            $response = new Response();
            $language = 'en';
            if ($user = Admin::getCurrentUser()) {
                $language = $user->getLanguage();
            } elseif ($user = Authentication::authenticateSession($event->getRequest())) {
                $language = $user->getLanguage();
            }

            $response->setContent($this->templating->render('I18nBundle::not_editable_aware_message.html.twig', ['adminLocale' => $language]));
            $event->setResponse($response);

            return;
        }

        $siteId = 1;
        if (Site::isSiteRequest() === true) {
            $site = Site::getCurrentSite();
            $siteId = $site->getRootId();
        }

        //if document is root, no language tag is required
        if ($this->document->getId() !== $siteId) {
            throw new \Exception(get_class($this->document) . ' (' . $this->document->getId() . ') does not have a valid language property!');
        }
    }

    protected function setDocumentLocale($i18nType)
    {
        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($this->document, $i18nType);

        $this->documentLocale = $documentLocaleData['documentLocale'];
        $this->documentLanguage = $documentLocaleData['documentLanguage'];
        $this->documentCountry = $documentLocaleData['documentCountry'];
    }

    protected function adjustRequestLocale()
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
