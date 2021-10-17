<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HeadMetaListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected HeadMeta $headMeta;
    protected ZoneManager $zoneManager;
    protected ContextManager $contextManager;

    public function __construct(
        HeadMeta $headMeta,
        ZoneManager $zoneManager,
        ContextManager $contextManager
    ) {
        $this->headMeta = $headMeta;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest']
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // just add meta data on master request
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->zoneManager->getCurrentZoneInfo('mode') !== 'country') {
            return;
        }

        $currentCountryIso = $this->contextManager->getCountryContext()->getCurrentCountryIso();
        if (empty($currentCountryIso)) {
            return;
        }

        $countryIso = $currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);
    }
}
