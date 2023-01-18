<?php

namespace I18nBundle\EventListener;

use Pimcore\Http\Request\Resolver\DocumentResolver;
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
            KernelEvents::REQUEST => ['onKernelRequest', 21], // before DocumentFallbackListener
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!$request->query->has('i18n_preview_locale') && !$request->query->has('i18n_preview_site')) {
            return;
        }

        $this->resolveSite($request);
        $this->resolveDocument($request);
    }

    protected function resolveSite(Request $request): void
    {
        if (!$request->query->has('i18n_preview_site')) {
            return;
        }

        $path = urldecode($request->getPathInfo());

        if (!$site = Site::getById($request->query->get('i18n_preview_site'))) {
            return;
        }

        Site::setCurrentSite($site);

        $this->siteResolver->setSite($request, $site);
        $this->siteResolver->setSitePath($request, $site->getRootPath() . $path);
    }

    protected function resolveDocument(Request $request): void
    {
        if (!$request->query->has('i18n_preview_locale')) {
            return;
        }

        $content = $request->get(DynamicRouter::CONTENT_KEY);

        if ($content instanceof Document) {
            return;
        }

        $path = $request->query->has('i18n_preview_site') ? $this->siteResolver->getSitePath($request) : $request;
        $nearestDocument = $this->documentService->getNearestDocumentByPath($path, false, $this->nearestDocumentTypes);

        if (!$nearestDocument instanceof Document) {
            return;
        }

        $this->documentResolver->setDocument($request, $nearestDocument);
    }
}
