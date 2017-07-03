<?php

namespace I18nBundle\EventListener\Frontend;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pimcore\Service\Request\DocumentResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Model\Document\Hardlink;
use I18nBundle\Pathfinder\Pathfinder;
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
     * @var Pathfinder
     */
    protected $pathfinder;

    /**
     * I18nRedirect constructor.
     *
     * @param DocumentResolver $documentResolver
     * @param Pathfinder      $pathfinder
     */
    public function __construct(DocumentResolver $documentResolver, Pathfinder $pathfinder)
    {
        $this->documentResolver = $documentResolver;
        $this->pathfinder = $pathfinder;
    }

    /**
     * {@inheritdoc}
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
            //$originDocument = $document->getHardLinkSource();
            $url = $this->pathfinder->checkPath( $event->getRequest()->get('path'));
            if($url !== FALSE) {
                $event->setResponse(new RedirectResponse($url));
            }
        }
    }
}