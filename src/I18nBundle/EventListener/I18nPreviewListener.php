<?php

namespace I18nBundle\EventListener;

use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Site;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;

class I18nPreviewListener implements EventSubscriberInterface
{
    protected array $nearestDocumentTypes;

    public function __construct(
        protected RequestHelper $requestHelper,
        protected DocumentResolver $documentResolver,
        protected SiteResolver $siteResolver,
        protected Document\Service $documentService
    ) {

        $this->nearestDocumentTypes = ['page', 'snippet', 'hardlink', 'link', 'folder'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 33], // before DocumentFallbackListener
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $validRequestContext =
            $request->attributes->get(PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT) === PimcoreContextResolver::CONTEXT_ADMIN ||
            $this->requestHelper->isFrontendRequestByAdmin($request);

        if ($validRequestContext === false) {
            return;
        }

        if ($this->getLocaleIdentifier($request) === null && $this->getSiteIdentifier($request) === null) {
            return;
        }

        $this->resolveSite($request);
        $this->resolveDocument($request);
    }

    protected function resolveSite(Request $request): void
    {
        $siteIdentifier = $this->getSiteIdentifier($request);

        if ($siteIdentifier === null) {
            return;
        }

        $path = urldecode($request->getPathInfo());

        if (!$site = Site::getById($siteIdentifier)) {
            return;
        }

        Site::setCurrentSite($site);

        $this->siteResolver->setSite($request, $site);
        $this->siteResolver->setSitePath($request, $site->getRootPath() . $path);
    }

    protected function resolveDocument(Request $request): void
    {
        $siteIdentifier = $this->getSiteIdentifier($request);
        $localeIdentifier = $this->getLocaleIdentifier($request);

        if ($localeIdentifier === null) {
            return;
        }

        $content = $request->get(DynamicRouter::CONTENT_KEY);

        if ($content instanceof Document) {
            return;
        }

        $path = $siteIdentifier !== null ? $this->siteResolver->getSitePath($request) : $request;
        $nearestDocument = $this->documentService->getNearestDocumentByPath($path, false, $this->nearestDocumentTypes);

        if (!$nearestDocument instanceof Document) {
            return;
        }

        $this->documentResolver->setDocument($request, $nearestDocument);
    }

    protected function getSiteIdentifier(Request $request): null|int|string
    {
        if ($request->query->has('i18n_preview_site')) {
            return $request->query->get('i18n_preview_site');
        } elseif ($request->query->has('i18n_site')) {
            return $request->query->get('i18n_site');
        }

        return null;
    }

    protected function getLocaleIdentifier(Request $request): ?string
    {
        if ($request->query->has('i18n_preview_locale')) {
            return $request->query->get('i18n_preview_locale');
        } elseif ($request->query->has('i18n_locale')) {
            return $request->query->get('i18n_locale');
        }

        return null;
    }

}
