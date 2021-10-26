<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Model\I18nZoneInterface;
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
    protected ZoneResolverInterface $zoneResolver;

    public function __construct(
        HeadMeta $headMeta,
        ZoneResolverInterface $zoneResolver
    ) {
        $this->headMeta = $headMeta;
        $this->zoneResolver = $zoneResolver;
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

        if ($request->attributes->get('_route') === 'fos_js_routing_js') {
            return;
        }

        $zone = $this->zoneResolver->getZone($request);

        if (!$zone instanceof I18nZoneInterface) {
            return;
        }

        if ($zone->getMode() !== 'country') {
            return;
        }

        $currentCountryIso = $zone->getContext()->getCountryIso();

        if (empty($currentCountryIso)) {
            return;
        }

        $countryIso = $currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);
    }
}
