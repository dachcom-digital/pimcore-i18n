<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\ZoneManager;
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
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * @param DocumentResolverService $documentResolverService
     * @param HeadMeta                $headMeta
     * @param ZoneManager      $zoneManager
     * @param ContextManager      $contextManager
     */
    public function __construct(
        DocumentResolverService $documentResolverService,
        HeadMeta $headMeta,
        ZoneManager $zoneManager,
        ContextManager $contextManager
    ) {
        $this->documentResolverService = $documentResolverService;
        $this->headMeta = $headMeta;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
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

        if($this->zoneManager->getCurrentZoneInfo('mode') !== 'country') {
            return;
        }

        $currentCountryIso = $this->contextManager->getCountryContext()->getCurrentCountryIso();

        if(empty($currentCountryIso)) {
            return;
        }

        $countryIso = strtolower($currentCountryIso) === 'global' ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);
    }
}