<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\ContextResolver\Context\AbstractContext;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\AbstractFrontendListener;
use Pimcore\Service\Request\DocumentResolver as DocumentResolverService;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Templating\Helper\HeadMeta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper
 */
class HeadMetaListener extends AbstractFrontendListener implements EventSubscriberInterface
{
    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

    /**
     * @var HeadMeta
     */
    protected $headMeta;

    /**
     * @var AbstractContext
     */
    protected $context;

    /**
     * @param DocumentResolverService $documentResolverService
     * @param HeadMeta                $headMeta
     * @param AbstractContext      $context
     */
    public function __construct(DocumentResolverService $documentResolverService, HeadMeta $headMeta, AbstractContext $context)
    {
        $this->documentResolverService = $documentResolverService;
        $this->headMeta = $headMeta;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest']
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // just add meta data on master request
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if($this->context->getCurrentContext() !== 'country') {
            return;
        }

        /*
        $document = $this->documentResolverService->getDocument($request);
        if ($document && $request->get('_route') == 'document_' . $document->getId()) {
            if ($document instanceof Page)) {
            }
        }
        */

        $currentCountryIso = $this->context->getCurrentCountryIso();

        if(empty($currentCountryIso)) {
            return;
        }

        $countryIso = strtolower($currentCountryIso) === 'global' ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);

    }
}