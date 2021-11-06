<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use I18nBundle\Http\RouteItemResolverInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
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
    protected RouteItemResolverInterface $routeItemResolver;

    public function __construct(
        HeadMeta $headMeta,
        RouteItemResolverInterface $routeItemResolver
    ) {
        $this->headMeta = $headMeta;
        $this->routeItemResolver = $routeItemResolver;
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

        $routeItem = $this->routeItemResolver->getRouteItem($request);

        if (!$routeItem instanceof RouteItemInterface) {
            return;
        }

        if ($routeItem->getI18nZone()->getMode() !== 'country') {
            return;
        }

        $currentCountryIso = $routeItem->getLocaleDefinition()->getCountryIso();

        if (empty($currentCountryIso)) {
            return;
        }

        $countryIso = $currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);
    }
}
