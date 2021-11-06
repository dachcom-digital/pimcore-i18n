<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Http\RouteItemResolverInterface;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HeadLinkListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected HeadLink $headLink;
    protected RouteItemResolverInterface $routeItemResolver;
    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;
    protected Config $pimcoreConfig;

    public function __construct(
        HeadLink $headLink,
        RouteItemResolverInterface $routeItemResolver,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        Config $pimcoreConfig
    ) {
        $this->headLink = $headLink;
        $this->routeItemResolver = $routeItemResolver;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->pimcoreConfig = $pimcoreConfig;
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

        // just add metadata on master request
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

        $hrefLinks = $routeItem->getI18nZone()->getLinkedLanguages();

        // Add x-default to main page!
        $xDefaultUrl = $this->getXDefaultLink($routeItem->getI18nZone()->getLocaleProviderDefaultLocale(), $hrefLinks);

        if (!is_null($xDefaultUrl)) {
            $this->headLink->appendAlternate($this->generateHrefLink($xDefaultUrl), false, false, ['hreflang' => 'x-default']);
        }

        foreach ($hrefLinks as $route) {
            $this->headLink->appendAlternate($this->generateHrefLink($route['url']), false, false, ['hreflang' => $route['hrefLang']]);
        }

        foreach ($this->headLink->getContainer() as $i => $item) {
            if (property_exists($item, 'rel') && $item->rel === 'alternate') {
                if (property_exists($item, 'type') && property_exists($item, 'title')) {
                    unset($item->type, $item->title);
                    $this->headLink->getContainer()->offsetSet($i, $item);
                }
            }
        }
    }

    /**
     * Define x-default url based on crawled pages.
     * country mode: get global page and default language from default/system settings
     * language mode: get page with default language from default/system settings.
     *
     * @throws \Exception
     */
    private function getXDefaultLink(?string $defaultLocale, array $hrefLinks = []): ?string
    {
        $hrefUrl = null;

        if (empty($hrefLinks)) {
            return null;
        }

        foreach ($hrefLinks as $link) {
            if ($link['locale'] === $defaultLocale) {
                $hrefUrl = $link['url'];

                break;
            }
        }

        return $hrefUrl;
    }

    /**
     * @throws \Exception
     */
    private function generateHrefLink(string $path): string
    {
        $allowTrailingSlash = $this->pimcoreConfig['documents']['allow_trailing_slash'];

        $endPath = rtrim($path, '/');
        if ($allowTrailingSlash !== 'no') {
            $endPath .= '/';
        }

        return $endPath;
    }
}
