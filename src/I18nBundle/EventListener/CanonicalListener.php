<?php

namespace I18nBundle\EventListener;

use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Model\Document\Hardlink\Wrapper;
use Pimcore\Model\Staticroute;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CanonicalListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;

    public function __construct(PimcoreDocumentResolverInterface $pimcoreDocumentResolver)
    {
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        if (!$document instanceof Wrapper\WrapperInterface) {
            return;
        }

        if (Staticroute::getCurrentRoute()) {
            return;
        }

        //only remove canonical link if hardlink source is the country wrapper
        if ($document->getHardLinkSource()->getPath() === '/') {
            $event->getResponse()->headers->remove('Link');
        }
    }
}
