<?php

namespace I18nBundle\EventListener;

use I18nBundle\Helper\CookieHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Model\I18nSiteInterface;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Registry\RedirectorRegistry;
use I18nBundle\Tool\System;
use Pimcore\Config;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;

class DetectorListener implements EventSubscriberInterface
{
    protected Configuration $configuration;
    protected CookieHelper $cookieHelper;
    protected RedirectorRegistry $redirectorRegistry;
    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;
    protected RequestValidatorHelper $requestValidatorHelper;
    protected ZoneResolverInterface $zoneResolver;

    public function __construct(
        Configuration $configuration,
        CookieHelper $cookieHelper,
        RedirectorRegistry $redirectorRegistry,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        ZoneResolverInterface $zoneResolver,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->configuration = $configuration;
        $this->cookieHelper = $cookieHelper;
        $this->redirectorRegistry = $redirectorRegistry;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->requestValidatorHelper = $requestValidatorHelper;
        $this->zoneResolver = $zoneResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => [
                ['onKernelRequest', 1] // after i18n startup
            ],
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $redirectUrl = null;
        $request = $event->getRequest();
        $document = $this->pimcoreDocumentResolver->getDocument($request);

        if (System::isInBackend($request)) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request, false)) {
            return;
        }

        if ($this->requestValidatorHelper->matchesI18nContext($request) === false) {
            return;
        }

        if (!$document instanceof Document) {
            return;
        }

        // request may contains valid locale (default is "en") so we need to check against given document!
        if (!empty($document->getProperty('language'))) {
            return;
        }

        $zone = $this->zoneResolver->getZone($request);

        if (!$zone instanceof I18nZoneInterface) {
            return;
        }

        $redirectorBag = new RedirectorBag([
            'zone'    => $zone,
            'request' => $request,
        ]);

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

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
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

        $registryConfig = $this->configuration->getConfig('registry');
        $available = isset($registryConfig['redirector']['cookie'])
            ? $registryConfig['redirector']['cookie']['enabled']
            : true;

        //check if we're allowed to bake a cookie at the first place!
        if ($available === false) {
            return;
        }

        $zone = $this->zoneResolver->getZone($event->getRequest());

        if (!$zone instanceof I18nZoneInterface) {
            return;
        }

        $zoneSites = $zone->getSites(true);
        $validUri = $this->getRedirectUrl(strtok($event->getRequest()->getUri(), '?'));

        $cookie = $this->cookieHelper->get($event->getRequest());

        //same domain, do nothing.
        if ($cookie !== null && $validUri === $cookie['url']) {
            return;
        }

        //check if url is valid
        $indexId = array_search($validUri, array_map(static function (I18nSiteInterface $site) {
            return $site->getUrl();
        }, $zoneSites), true);

        if ($indexId === false) {
            return;
        }

        $this->cookieHelper->set($event->getResponse(), [
            'url'      => $validUri,
            'locale'   => $zone->getContext()->getLocale(),
            'language' => $zone->getContext()->getLanguageIso(),
            'country'  => $zone->getContext()->getCountryIso()
        ]);

    }

    protected function getRedirectUrl(string $path): string
    {
        $config = Config::getSystemConfiguration('documents');

        $endPath = rtrim($path, '/');

        if ($config['allow_trailing_slash'] !== 'no') {
            $endPath .= '/';
        }

        return $endPath;
    }
}
