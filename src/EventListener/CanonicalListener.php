<?php

namespace I18nBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\AbstractFrontendListener;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Staticroute;
use Pimcore\Service\Request\DocumentResolver;
use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CanonicalListener extends AbstractFrontendListener implements EventSubscriberInterface
{
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param DocumentResolver $documentResolver
     */
    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        //@todo: check if it's a real i18n hardlink element.
        if ($document instanceof WrapperInterface && !Staticroute::getCurrentRoute()) {
            $event->getResponse()->headers->remove('Link');
        }
    }
}
