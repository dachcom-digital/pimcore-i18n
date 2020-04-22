<?php

namespace I18nBundle\EventListener;

use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink\Wrapper;
use Pimcore\Model\Staticroute;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CanonicalListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

    /**
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     */
    public function __construct(PimcoreDocumentResolverInterface $pimcoreDocumentResolver)
    {
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
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

        if (!$document instanceof Wrapper\WrapperInterface && !Staticroute::getCurrentRoute()) {
            return;
        }

        /** @var Wrapper $wrapperDocument */
        $wrapperDocument = $document;
        //only remove canonical link if hardlink source is the country wrapper
        if ($wrapperDocument->getHardLinkSource()->getPath() === '/') {
            $event->getResponse()->headers->remove('Link');
        }
    }
}
