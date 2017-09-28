<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver as DocumentResolverService;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Templating\Helper\HeadLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper
 */
class HeadLinkListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

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
     * HeadLinkListener constructor.
     *
     * @param DocumentResolverService $documentResolverService
     * @param HeadLink                $headLink
     * @param ZoneManager             $zoneManager
     * @param ContextManager          $contextManager
     * @param PathGeneratorManager    $pathGeneratorManager
     */
    public function __construct(
        DocumentResolverService $documentResolverService,
        HeadLink $headLink,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager
    ) {
        $this->documentResolverService = $documentResolverService;
        $this->headLink = $headLink;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
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

        $document = $this->documentResolverService->getDocument($request);
        $hrefLinks = $this->pathGeneratorManager->getPathGenerator()->getUrls($document);

        //add x-default to main page!
        $xDefaultUrl = $this->getXDefaultLink($hrefLinks);

        if (!is_null($xDefaultUrl)) {
            $this->headLink->appendAlternate($this->generateHrefLink($xDefaultUrl), FALSE, FALSE, ['hreflang' => 'x-default']);
        }

        foreach ($hrefLinks as $route) {
            $this->headLink->appendAlternate($this->generateHrefLink($route['url']), FALSE, FALSE, ['hreflang' => $route['hrefLang']]);
        }
    }

    /**
     * Define xDefault Url based on crawled pages.
     * country mode: get global page and default language from system settings
     * language mode: get page with default language from system settings
     *
     * @param array $hrefLinks
     *
     * @return string
     */
    private function getXDefaultLink($hrefLinks = [])
    {
        $hrefUrl = NULL;

        if (empty($hrefLinks)) {
            return $hrefUrl;
        }

        $defaultLanguage = $this->zoneManager->getCurrentZoneLanguageAdapter()->getDefaultLanguage();

        foreach ($hrefLinks as $link) {

            $countryIsoTag = NULL;
            if ($this->zoneManager->getCurrentZoneInfo('mode') === 'country') {
                $countryIsoTag = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
            }

            if ($link['languageIso'] === $defaultLanguage && $link['countryIso'] === $countryIsoTag) {
                $hrefUrl = $link['url'];
                break;
            }
        }

        return $hrefUrl;
    }

    /**
     * @param $path
     *
     * @return string
     */
    private function generateHrefLink($path)
    {
        $config = \Pimcore\Config::getSystemConfig();

        $endPath = rtrim($path, '/');
        if ($config->documents->allowtrailingslash !== 'no') {
            $endPath = $endPath . '/';
        }

        return $endPath;
    }
}