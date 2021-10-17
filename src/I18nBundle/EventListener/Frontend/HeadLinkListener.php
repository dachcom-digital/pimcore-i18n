<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HeadLinkListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected HeadLink $headLink;
    protected ZoneManager $zoneManager;
    protected ContextManager $contextManager;
    protected PathGeneratorManager $pathGeneratorManager;
    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;
    protected array $pimcoreConfig;

    public function __construct(
        HeadLink $headLink,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        array $pimcoreConfig
    ) {
        $this->headLink = $headLink;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
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

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        $hrefLinks = $this->pathGeneratorManager->getPathGenerator()->getUrls($document);

        // Add x-default to main page!
        $xDefaultUrl = $this->getXDefaultLink($hrefLinks);

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
    private function getXDefaultLink(array $hrefLinks = []): ?string
    {
        $hrefUrl = null;

        if (empty($hrefLinks)) {
            return null;
        }

        $defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();

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
