<?php

namespace I18nBundle\EventListener;

use I18nBundle\Adapter\Redirector\CookieRedirector;
use I18nBundle\Helper\RequestValidatorHelper;
use I18nBundle\Manager\RouteItemManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Http\Request\Resolver\SiteResolver;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Registry\RedirectorRegistry;
use Pimcore\Model\Site;
use Pimcore\Routing\RedirectHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;

class PimcoreRedirectListener implements EventSubscriberInterface
{
    protected RedirectorRegistry $redirectorRegistry;
    protected RouteItemManager $routeItemManager;
    protected RedirectHandler $redirectHandler;
    protected SiteResolver $siteResolver;
    protected RequestValidatorHelper $requestValidatorHelper;

    public function __construct(
        RedirectorRegistry $redirectorRegistry,
        RouteItemManager $routeItemManager,
        RedirectHandler $redirectHandler,
        SiteResolver $siteResolver,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->redirectorRegistry = $redirectorRegistry;
        $this->routeItemManager = $routeItemManager;
        $this->redirectHandler = $redirectHandler;
        $this->siteResolver = $siteResolver;
        $this->requestValidatorHelper = $requestValidatorHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelRedirectException', 65]],     // before pimcore frontend routing listener (redirect check)
            KernelEvents::REQUEST   => [['onKernelRedirectRequest', 513]],      // before pimcore frontend routing listener (redirect check)
        ];
    }

    public function onKernelRedirectException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $response = $this->checkI18nPimcoreRedirects($event->getRequest());
        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    public function onKernelRedirectRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        if ($this->requestValidatorHelper->isValidForRedirect($event->getRequest(), false) === false) {
            return;
        }

        $this->resolveSite($event->getRequest());

        $response = $this->checkI18nPimcoreRedirects($event->getRequest(), true);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    protected function checkI18nPimcoreRedirects(Request $request, bool $override = false): ?Response
    {
        $response = $this->redirectHandler->checkForRedirect($request, $override);
        if (!$response instanceof RedirectResponse) {
            return null;
        }

        $oldTargetUrl = $response->getTargetUrl();
        $oldTargetUrlParts = parse_url($oldTargetUrl);

        preg_match('/\{i18n_localized_target_page=([0-9]+)\}/', $response->getTargetUrl(), $matches);

        if (!is_array($matches) || count($matches) !== 2) {
            return $response;
        }

        $validDecision = null;
        $documentId = $matches[1];
        $document = Document::getById($documentId);

        if (!$document instanceof Document) {
            return $response;
        }

        if (!$request->attributes->has('pimcore_request_source')) {
            $request->attributes->set('pimcore_request_source', sprintf('document_%d', $document->getId()));
        }

        $routeItem = $this->routeItemManager->buildRouteItemByRequest($request, $document);

        if (!$routeItem instanceof RouteItemInterface) {
            return $response;
        }

        $redirectorBag = new RedirectorBag([
            'zone'    => $routeItem->getI18nZone(),
            'request' => $request
        ]);

        foreach ($this->redirectorRegistry->all() as $redirector) {
            // do not use redirector with storage functionality
            if ($redirector instanceof CookieRedirector) {
                continue;
            }

            $redirector->makeDecision($redirectorBag);
            $decision = $redirector->getDecision();

            if ($decision['valid'] === true) {
                $validDecision = $decision;
            }

            $redirectorBag->addRedirectorDecisionToBag($redirector->getName(), $decision);
        }

        if ($validDecision === null) {
            $response->setTargetUrl($document->getFullPath());

            return $response;
        }

        $localizedUrls = $routeItem->getI18nZone()->getLinkedLanguages(false);

        if (count($localizedUrls) === 0) {
            $response->setTargetUrl($document->getFullPath());

            return $response;
        }

        $newTargetUrl = null;
        foreach ($localizedUrls as $localizedUrl) {
            if ($localizedUrl['locale'] === $validDecision['locale']) {
                $newTargetUrl = $localizedUrl['url'];
            }
        }

        // no link found, use first one we've found.
        if ($newTargetUrl === null) {
            $response->setTargetUrl($localizedUrls[0]['url']);

            return $response;
        }

        if (isset($oldTargetUrlParts['query']) && !empty($oldTargetUrlParts['query'])) {
            $newTargetUrl .= !str_contains($newTargetUrl, '?') ? '?' : '&';
            $newTargetUrl .= $oldTargetUrlParts['query'];
        }

        $response->setTargetUrl($newTargetUrl);

        return $response;
    }

    protected function resolveSite(Request $request): string
    {
        $path = urldecode($request->getPathInfo());

        if ($this->requestValidatorHelper->isFrontendRequestByAdmin($request)) {
            return $path;
        }

        $host = $request->getHost();
        $site = Site::getByDomain($host);

        if (!$site instanceof Site) {
            return $path;
        }

        $path = $site->getRootPath() . $path;

        Site::setCurrentSite($site);

        $this->siteResolver->setSite($request, $site);
        $this->siteResolver->setSitePath($request, $path);

        return $path;
    }
}
