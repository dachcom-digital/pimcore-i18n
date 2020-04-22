<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document\Hardlink;
use I18nBundle\Finder\PathFinder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LinkPathListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var PathFinder
     */
    protected $pathfinder;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

    /**
     * @param PathFinder                       $pathfinder
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     */
    public function __construct(
        PathFinder $pathfinder,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver
    ) {
        $this->pathfinder = $pathfinder;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    /**
     * Redirect Pimcore Link Url to right i18n context.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        if (!$document instanceof Document) {
            return;
        }

        $path = $event->getRequest()->get('path');
        if (empty($path)) {
            return;
        }

        if ($document instanceof Hardlink\Wrapper\Link) {
            $i18nFrontEndPath = $this->pathfinder->checkPath($path);
            if ($i18nFrontEndPath !== false) {
                $event->setResponse(new RedirectResponse($i18nFrontEndPath));
            }
        }
    }
}
