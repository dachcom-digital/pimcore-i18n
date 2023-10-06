<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
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

    public function __construct(
        protected HeadLink $headLink,
        protected I18nContextResolverInterface $i18nContextResolver,
        protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        protected Config $pimcoreConfig
    ) {
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

        $hrefLinks = $i18nContext->getLinkedLanguages();

        // Add x-default to main page!
        $xDefaultUrl = $this->getXDefaultLink($i18nContext->getZoneDefaultLocale(), $hrefLinks);

        if (!is_null($xDefaultUrl)) {
            $this->headLink->appendAlternate($this->generateHrefLink($xDefaultUrl), false, false, ['webLink' => false, 'hreflang' => 'x-default']);
        }

        foreach ($hrefLinks as $route) {
            $this->headLink->appendAlternate($this->generateHrefLink($route['url']), false, false, ['webLink' => false, 'hreflang' => $route['hrefLang']]);
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
