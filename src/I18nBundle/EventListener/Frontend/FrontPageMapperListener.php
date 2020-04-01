<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document\Hardlink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FrontPageMapperListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -255] //after ElementListener
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
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

        if ($document instanceof Hardlink\Wrapper\WrapperInterface) {
            /** @var Hardlink\Wrapper $wrapperDocument */
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
}
