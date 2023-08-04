<?php

namespace I18nBundle\EventListener;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Helper\CookieHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Model\ZoneSiteInterface;
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
    public function __construct(
        protected Configuration $configuration,
        protected CookieHelper $cookieHelper,
        protected RedirectorRegistry $redirectorRegistry,
        protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        protected I18nContextResolverInterface $i18nContextResolver,
        protected RequestValidatorHelper $requestValidatorHelper
    ) {
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

        $i18nContext = $this->i18nContextResolver->getContext($request);

        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        $redirectorBag = new RedirectorBag([
            'i18nContext' => $i18nContext,
            'request'     => $request,
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

        $i18nContext = $this->i18nContextResolver->getContext($event->getRequest());

        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        $zone = $i18nContext->getZone();

        $zoneSites = $zone->getSites(true);
        $validUri = $this->getRedirectUrl(strtok($event->getRequest()->getUri(), '?'));

        $cookie = $this->cookieHelper->get($event->getRequest());

        //same domain, do nothing.
        if ($cookie !== null && $validUri === $cookie['url']) {
            return;
        }

        //check if url is valid
        $indexId = array_search($validUri, array_map(static function (ZoneSiteInterface $site) {
            return $site->getUrl();
        }, $zoneSites), true);

        if ($indexId === false) {
            return;
        }

        $this->cookieHelper->set($event->getResponse(), [
            'url'      => $validUri,
            'locale'   => $i18nContext->getLocaleDefinition()->getLocale(),
            'language' => $i18nContext->getLocaleDefinition()->getLanguageIso(),
            'country'  => $i18nContext->getLocaleDefinition()->getCountryIso()
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
