<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\Exception\VirtualProxyPathException;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\ZoneSiteInterface;
use Pimcore\Model\Document as PimcoreDocument;
use Pimcore\Tool\Frontend;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Document extends AbstractPathGenerator
{
    private RouterInterface $router;
    protected array $cachedUrls = [];

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options
            ->setDefaults(['document' => null])
            ->setRequired(['document'])
            ->setAllowedTypes('document', [PimcoreDocument::class]);
    }

    public function getUrls(I18nContextInterface $i18nContext, bool $onlyShowRootLanguages = false): array
    {
        /** @var PimcoreDocument $document */
        $document = $i18nContext->getRouteItem()->getEntity();

        if (isset($this->cachedUrls[$document->getId()])) {
            return $this->cachedUrls[$document->getId()];
        }

        $urls = $this->resolveDocumentRoutes($i18nContext, $document, $onlyShowRootLanguages);

        $this->cachedUrls[$document->getId()] = $urls;

        return $urls;
    }

    private function resolveDocumentRoutes(I18nContextInterface $i18nContext, PimcoreDocument $document, bool $onlyShowRootLanguages = false): array
    {
        $routes = [];
        $zoneSites = $i18nContext->getZone()->getSites(true);
        $routeItem = $i18nContext->getRouteItem();

        $rootDocumentIndexId = array_search($document->getId(), array_map(static function (ZoneSiteInterface $site) {
            return $site->getRootId();
        }, $zoneSites), true);

        if ($onlyShowRootLanguages === true || $rootDocumentIndexId !== false) {
            foreach ($zoneSites as $zoneSite) {
                $routes[] = [
                    'languageIso'      => $zoneSite->getLanguageIso(),
                    'countryIso'       => $zoneSite->getCountryIso(),
                    'locale'           => $zoneSite->getLocale(),
                    'hrefLang'         => $zoneSite->getHrefLang(),
                    'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                    'key'              => $document->getKey(),
                    'url'              => $zoneSite->getUrl()
                ];
            }

            return $routes;
        }

        $virtualProxyZoneSites = [];
        $virtualProxyZoneSiteDocuments = [];

        $service = new PimcoreDocument\Service();
        $translations = $service->getTranslations($document);

        //if no translation has been found, add document itself:
        if (empty($translations) && $document->hasProperty('language')) {
            if ($document instanceof PimcoreDocument\Hardlink\Wrapper\WrapperInterface) {
                $locale = $document->getHardLinkSource()->getSourceDocument()?->getProperty('language');
            } else {
                $locale = $document->getProperty('language');
            }

            $translations = [$locale => $document->getId()];
        }

        foreach ($zoneSites as $zoneSite) {
            if (empty($zoneSite->getLocale())) {
                continue;
            }

            $pageInfoLocale = $zoneSite->getLocale();

            // document/translation does not exist.
            // if page info is type of "hardlink", we need to add them to a second check
            if (!isset($translations[$pageInfoLocale])) {
                if ($zoneSite->getType() === 'hardlink') {
                    $virtualProxyZoneSites[] = $zoneSite;
                }

                continue;
            }

            try {
                /** @var PimcoreDocument\Page $document */
                $document = PimcoreDocument::getById($translations[$pageInfoLocale]);
            } catch (\Exception $e) {
                continue;
            }

            if (!$document->isPublished()) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $zoneSite->getLanguageIso(),
                'countryIso'       => $zoneSite->getCountryIso(),
                'locale'           => $zoneSite->getLocale(),
                'hrefLang'         => $zoneSite->getHrefLang(),
                'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                'url'              => $this->generateLink($routeItem, $document)
            ];

            $virtualProxyZoneSiteDocuments[] = $document;
        }

        if (count($virtualProxyZoneSites) === 0) {
            return $routes;
        }

        foreach ($virtualProxyZoneSites as $virtualProxyZoneSite) {
            $sameLanguageContextIndex = array_search($virtualProxyZoneSite->getLanguageIso(), array_column($routes, 'languageIso'), true);
            if ($sameLanguageContextIndex === false) {
                continue;
            }

            try {
                $virtualProxyUrl = $this->generateLink($routeItem, $virtualProxyZoneSiteDocuments[$sameLanguageContextIndex], $virtualProxyZoneSite);
            } catch (VirtualProxyPathException $e) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $virtualProxyZoneSite->getLanguageIso(),
                'countryIso'       => $virtualProxyZoneSite->getCountryIso(),
                'locale'           => $virtualProxyZoneSite->getLocale(),
                'hrefLang'         => $virtualProxyZoneSite->getHrefLang(),
                'localeUrlMapping' => $virtualProxyZoneSite->getLocaleUrlMapping(),
                'url'              => $virtualProxyUrl
            ];
        }

        return $routes;
    }

    protected function generateLink(RouteItemInterface $routeItem, PimcoreDocument $document, ?ZoneSiteInterface $virtualProxyZoneSite = null): string
    {
        $context = [];

        if ($virtualProxyZoneSite instanceof ZoneSiteInterface) {
            $context['virtualProxyZoneSite'] = $virtualProxyZoneSite;
        }

        if (null !== $site = Frontend::getSiteForDocument($document)) {
            $context['site'] = $site;
        }

        $routeParameters = [
            Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => [
                'type'            => RouteItemInterface::DOCUMENT_ROUTE,
                'entity'          => $document,
                'routeName'       => '',
                'routeParameters' => [
                    '_locale' => $document->getProperty('language')
                ],
                'routeAttributes' => $routeItem->getRouteAttributes(),
                'context'         => $context
            ]
        ];

        return $this->router->generate('', $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
