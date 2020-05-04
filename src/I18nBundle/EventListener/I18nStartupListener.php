<?php

namespace I18nBundle\EventListener;

use Pimcore\Cache;
use Pimcore\Model\Site;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;
use Symfony\Component\Templating\EngineInterface;

class I18nStartupListener implements EventSubscriberInterface
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

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
     * @param EngineInterface                  $templating
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     * @param ZoneManager                      $zoneManager
     * @param ContextManager                   $contextManager
     * @param PathGeneratorManager             $pathGeneratorManager
     * @param EditmodeResolver                 $editmodeResolver
     * @param DocumentHelper                   $documentHelper
     * @param RequestValidatorHelper           $requestValidatorHelper
     */
    public function __construct(
        EngineInterface $templating,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        EditmodeResolver $editmodeResolver,
        DocumentHelper $documentHelper,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->templating = $templating;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
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
            ]
        ];
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

        $request = $event->getRequest();
        $document = $this->pimcoreDocumentResolver->getDocument($request);

        if (!$document instanceof Document) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request)) {
            return;
        }

        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($document, 'language');

        $requestSource = $request->attributes->get('pimcore_request_source');
        if ($requestSource === 'staticroute' && !empty($documentLocaleData['documentLocale']) && $request->attributes->get('_locale') !== $documentLocaleData['documentLocale']) {
            $this->adjustRequestLocale($request, $documentLocaleData['documentLocale']);
        }
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

        $this->initI18nSystem($event->getRequest(), null);

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

        $request = $event->getRequest();
        $document = $this->pimcoreDocumentResolver->getDocument($request);

        if (!$document instanceof Document) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request)) {
            return;
        }

        $this->initI18nSystem($request, $document);

        $currentRouteName = $request->get('_route');
        $requestSource = $request->attributes->get('pimcore_request_source');

        $validLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
        $i18nType = $this->zoneManager->getCurrentZoneInfo('mode');

        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($document, $i18nType);

        $documentLocale = $documentLocaleData['documentLocale'];
        $documentLanguage = $documentLocaleData['documentLanguage'];
        $documentCountry = $documentLocaleData['documentCountry'];

        /**
         * If a root node hardlink is requested e.g. /en-us, pimcore gets the locale from the source, which is "quite" wrong.
         */
        $requestLocale = $request->getLocale();
        if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            if (!empty($documentLocale) && $documentLocale !== $requestLocale) {
                $this->adjustRequestLocale($request, $documentLocale);
            }
        }

        $validRoute = false;
        if ($requestSource === 'staticroute' || $currentRouteName === 'document_' . $document->getId()) {
            $validRoute = true;
        }

        // @todo:
        // currently, redirect works only with pimcore documents and static routes.
        // symfony routes will be ignored.
        if ($validRoute === false) {
            return;
        }

        if (empty($documentLocale)) {
            $this->setNotEditableAwareMessage($document, $event);
        }

        $validLocale = !empty($documentLocale) && array_search($documentLocale, array_column($validLocales, 'locale')) !== false;

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_CONTEXT, true);

        //Set Locale.
        if ($validLocale === true) {
            Cache\Runtime::set('i18n.locale', $documentLocale);
            Cache\Runtime::set('i18n.languageIso', $documentLanguage);
        }

        //Set Country. This variable is only !false if i18n country is active
        if (!empty($documentCountry)) {
            Cache\Runtime::set('i18n.countryIso', $documentCountry);
        }
    }

    /**
     * @param Request       $request
     * @param Document|null $document
     *
     * @throws \Exception
     */
    private function initI18nSystem($request, ?Document $document)
    {
        $this->zoneManager->initZones();

        if ($document instanceof Document) {
            if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                /** @var Document\Hardlink\Wrapper $wrapperDocument */
                $wrapperDocument = $document;
                $document = $wrapperDocument->getHardLinkSource();
            }
        }

        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'), $document);
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));
    }

    /**
     * @param Document         $document
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    protected function setNotEditableAwareMessage(Document $document, GetResponseEvent $event)
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
        if ($document->getId() !== $siteId) {
            throw new \Exception(get_class($document) . ' (' . $document->getId() . ') does not have a valid language property!');
        }
    }

    /**
     * @param Request $request
     * @param string  $documentLocale
     */
    protected function adjustRequestLocale(Request $request, string $documentLocale)
    {
        // set request locale
        $request->attributes->set('_locale', $documentLocale);
        $request->setLocale($documentLocale);

        //set route param locale
        $routeParams = $request->attributes->get('_route_params');
        if (!is_array($routeParams)) {
            $routeParams = [];
        }

        $routeParams['_locale'] = $documentLocale;
        $request->attributes->set('_route_params', $routeParams);
    }
}
