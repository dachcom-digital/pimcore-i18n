<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Templating\Helper\HeadLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper.
 */
class HeadLinkListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var HeadLink
     */
    protected $headLink;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * @var PathGeneratorManager
     */
    protected $pathGeneratorManager;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

    /**
     * @var array
     */
    protected $pimcoreConfig;

    /**
     * @param HeadLink                         $headLink
     * @param ZoneManager                      $zoneManager
     * @param ContextManager                   $contextManager
     * @param PathGeneratorManager             $pathGeneratorManager
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     * @param array                            $pimcoreConfig
     */
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
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // just add metadata on master request
        if (!$event->isMasterRequest()) {
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
     * @param array $hrefLinks
     *
     * @return string
     * @throws \Exception
     */
    private function getXDefaultLink($hrefLinks = [])
    {
        $hrefUrl = null;

        if (empty($hrefLinks)) {
            return $hrefUrl;
        }

        $defaultCountry = null;
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
     * @param string $path
     *
     * @return string
     * @throws \Exception
     */
    private function generateHrefLink($path)
    {
        $allowTrailingSlash = $this->pimcoreConfig['documents']['allow_trailing_slash'];

        $endPath = rtrim($path, '/');
        if ($allowTrailingSlash !== 'no') {
            $endPath = $endPath . '/';
        }

        return $endPath;
    }
}
