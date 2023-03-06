<?php

namespace I18nBundle\EventListener;

use I18nBundle\Helper\CookieHelper;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Adapter\Redirector\RedirectorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Registry\RedirectorRegistry;
use I18nBundle\Tool\System;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;
use Pimcore\Cache;

class DetectorListener implements EventSubscriberInterface
{
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
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var RequestValidatorHelper
     */
    protected $requestValidatorHelper;

    /**
     * @param Configuration                    $configuration
     * @param CookieHelper                     $cookieHelper
     * @param RedirectorRegistry               $redirectorRegistry
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     * @param ZoneManager                      $zoneManager
     * @param ContextManager                   $contextManager
     * @param DocumentHelper                   $documentHelper
     * @param RequestValidatorHelper           $requestValidatorHelper
     */
    public function __construct(
        Configuration $configuration,
        CookieHelper $cookieHelper,
        RedirectorRegistry $redirectorRegistry,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        DocumentHelper $documentHelper,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->configuration = $configuration;
        $this->cookieHelper = $cookieHelper;
        $this->redirectorRegistry = $redirectorRegistry;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->documentHelper = $documentHelper;
        $this->requestValidatorHelper = $requestValidatorHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => [
                ['onKernelRequest', 1] // after i18n startup
            ],
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
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

        $redirectUrl = null;
        $request = $event->getRequest();
        $document = $this->pimcoreDocumentResolver->getDocument($request);

        if (System::isInBackend($request)) {
            return;
        }

        if (!$document instanceof Document) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request, false)) {
            return;
        }

        if ($this->requestValidatorHelper->matchesI18nContext($request) === false) {
            return;
        }

        $validLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
        $defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();
        $i18nType = $this->zoneManager->getCurrentZoneInfo('mode');

        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($document, $i18nType);

        $documentLocale = $documentLocaleData['documentLocale'];
        $documentCountry = $documentLocaleData['documentCountry'];

        $validLocale = !empty($documentLocale) && array_search($documentLocale, array_column($validLocales, 'locale')) !== false;

        // locale is available, no redirect required.
        if ($validLocale !== false) {
            return;
        }

        $options = [
            'i18nType'        => $i18nType,
            'request'         => $request,
            'document'        => $document,
            'documentLocale'  => $documentLocale,
            'documentCountry' => $documentCountry,
            'defaultLocale'   => $defaultLocale
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

        if ($redirectUrl !== null) {
            $status = $this->configuration->getConfig('redirect_status_code');
            $event->setResponse(new RedirectResponse($this->getRedirectUrl($redirectUrl), $status));
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

        if ($event->getRequest()->getPathInfo() === '/') {
            return;
        }

        if ($this->requestValidatorHelper->matchesDefaultPimcoreContext($event->getRequest()) === false) {
            return;
        }

        if ($this->requestValidatorHelper->matchesI18nContext($event->getRequest()) === false) {
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
}
