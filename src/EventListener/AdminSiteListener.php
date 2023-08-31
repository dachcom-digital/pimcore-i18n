<?php

namespace I18nBundle\EventListener;

use I18nBundle\Resolver\PimcoreAdminSiteResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminSiteListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(
        protected RequestHelper $requestHelper,
        protected PimcoreAdminSiteResolverInterface $adminSiteResolver
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run after pimcore DocumentFallbackListener (20)
            KernelEvents::REQUEST => ['onKernelRequest', 19],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->resolveAdminContextSite($event->getRequest());
    }

    protected function resolveAdminContextSite(Request $request): void
    {
        $document = null;
        if ($this->requestHelper->isFrontendRequestByAdmin($request) && $request->attributes->has(DynamicRouter::CONTENT_KEY)) {
            $document = $request->attributes->get(DynamicRouter::CONTENT_KEY);
        } elseif ($request->attributes->get('_route') === 'pimcore_admin_document_page_areabrick-render-index-editmode' && $request->request->has('documentId')) {
            $document = Document::getById($request->request->get('documentId'));
        }

        if ($document === null) {
            return;
        }

        $site = Frontend::getSiteForDocument($document);

        if (!$site instanceof Site) {
            return;
        }

        $this->adminSiteResolver->setAdminSite($request, $site);
    }
}
