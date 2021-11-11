<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\Http\I18nContextResolverInterface;
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
    protected I18nContextResolverInterface $i18nContextResolver;

    public function __construct(
        HeadMeta $headMeta,
        I18nContextResolverInterface $i18nContextResolver
    ) {
        $this->headMeta = $headMeta;
        $this->i18nContextResolver = $i18nContextResolver;
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

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($request->attributes->get('_route') === 'fos_js_routing_js') {
            return;
        }

        $i18nContext = $this->i18nContextResolver->getContext($request);

        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        if ($i18nContext->getZone()->getMode() !== 'country') {
            return;
        }

        $currentCountryIso = $i18nContext->getLocaleDefinition()->getCountryIso();

        if (empty($currentCountryIso)) {
            return;
        }

        $countryIso = $currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE ? 'international' : $currentCountryIso;
        $this->headMeta->appendName('country', $countryIso);
    }
}
