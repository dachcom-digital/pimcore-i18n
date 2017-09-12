<?php

namespace I18nBundle\EventListener\Frontend;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pimcore\Http\Request\Resolver\DocumentResolver;
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
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var PathFinder
     */
    protected $pathfinder;

    /**
     * I18nRedirect constructor.
     *
     * @param DocumentResolver $documentResolver
     * @param PathFinder       $pathfinder
     */
    public function __construct(DocumentResolver $documentResolver, PathFinder $pathfinder)
    {
        $this->documentResolver = $documentResolver;
        $this->pathfinder = $pathfinder;
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

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $path = $event->getRequest()->get('path');
        if (empty($path)) {
            return;
        }

        if ($document instanceof Hardlink\Wrapper\Link) {
            $i18nFrontEndPath = $this->pathfinder->checkPath($path);
            if ($i18nFrontEndPath !== FALSE) {
                $event->setResponse(new RedirectResponse($i18nFrontEndPath));
            }
        }
    }
}