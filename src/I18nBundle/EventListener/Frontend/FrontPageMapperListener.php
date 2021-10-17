<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FrontPageMapperListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected DocumentResolver $documentResolver;

    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -255] //after ElementListener
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // use original document resolver to allow using document override!
        $document = $this->documentResolver->getDocument($request);
        if (!$document instanceof Document) {
            return;
        }

        if (!$document instanceof Hardlink\Wrapper\WrapperInterface) {
            return;
        }

        /** @var Hardlink\Wrapper\WrapperInterface $wrapperDocument */
        $wrapperDocument = $document;
        if ($wrapperDocument->getHardLinkSource()->getFullPath() === $document->getFullPath()) {
            $mapDocument = $wrapperDocument->getHardLinkSource()->getProperty('front_page_map');
            if (!empty($mapDocument)) {
                $request->attributes->set(Definitions::FRONT_PAGE_MAP, ['id' => $document->getId(), 'key' => $document->getKey()]);
                $this->documentResolver->setDocument($request, $mapDocument);
            }
        }

    }
}
