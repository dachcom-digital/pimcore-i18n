<?php

namespace I18nBundle\Builder;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\ZoneSite;
use I18nBundle\Model\ZoneSiteInterface;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\SiteRequestContext;
use Doctrine\DBAL\Connection;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

class ZoneSitesBuilder
{
    protected ?string $generalDomain;
    protected Connection $db;
    protected Configuration $configuration;

    public function __construct(
        ?string $generalDomain,
        Connection $db,
        Configuration $configuration
    ) {
        $this->generalDomain = $generalDomain;
        $this->db = $db;
        $this->configuration = $configuration;
    }

    public function buildZoneSites(ZoneInterface $zone, RouteItemInterface $routeItem, bool $fullBootstrap = false): array
    {
        $zoneSites = [];

        if ($fullBootstrap === false) {
            // we don't have a zone id, so no site context is needed!
            if ($zone->getId() === null) {
                $virtualZoneSite = $this->buildVirtualZoneSite();
                $zoneSites[] = $this->createZoneSite($zone, $routeItem, null, $virtualZoneSite['mainDomain'], $virtualZoneSite['rootId'], false);
            } else {
                /** @var Site $pimcoreSite */
                $pimcoreSite = $routeItem->getRouteContextBag()->get('site');
                $zoneSites[] = $this->createZoneSite($zone, $routeItem, $pimcoreSite, $pimcoreSite->getMainDomain(), $pimcoreSite->getRootId(), false);
            }

            return $zoneSites;
        }

        $availablePimcoreSites = $this->fetchAvailablePimcoreSites();

        //it's a simple page, no sites: create a virtual one
        if (count($availablePimcoreSites) === 0) {
            $availablePimcoreSites[] = $this->buildVirtualZoneSite();
        }

        foreach ($availablePimcoreSites as $pimcoreRawSite) {
            $pimcoreSite = array_key_exists('id', $pimcoreRawSite) ? Site::getById($pimcoreRawSite['id']) : null;
            $zoneSite = $this->createZoneSite($zone, $routeItem, $pimcoreSite, $pimcoreRawSite['mainDomain'], $pimcoreRawSite['rootId'], $fullBootstrap);
            if ($zoneSite instanceof ZoneSiteInterface) {
                $zoneSites[] = $zoneSite;
            }
        }

        return $zoneSites;
    }

    protected function createZoneSite(
        ZoneInterface $zone,
        RouteItemInterface $routeItem,
        ?Site $pimcoreSite,
        string $mainDomain,
        int $rootId,
        bool $fullBootstrap
    ): ?ZoneSiteInterface {

        $routeItemLocale = $routeItem->getLocaleFragment();
        $domainDoc = Document::getById($rootId);

        if (!$domainDoc instanceof Document) {
            return null;
        }

        $zoneDomainConfiguration = null;
        $valid = $zone->getId() === null;

        if ($zone->getId() !== null && !empty($zone->getDomains())) {
            foreach ($zone->getDomains() as $zoneDomain) {
                $currentZoneDomainHost = is_array($zoneDomain) ? $zoneDomain[0] : $zoneDomain;
                if ($mainDomain === $currentZoneDomainHost) {
                    $zoneDomainConfiguration = $zoneDomain;
                    $valid = true;

                    break;
                }
            }
        }

        $isPublishedMode = $domainDoc->isPublished() === true || $routeItem->isFrontendRequestByAdmin();
        if ($valid === false || $isPublishedMode === false) {
            return null;
        }

        $hrefLang = '';
        $docRealLanguageIso = '';
        $docCountryIso = null;
        $docLocale = $domainDoc->getProperty('language');
        $siteRequestContext = $this->generateSiteRequestContext($mainDomain, $zoneDomainConfiguration);

        if (!empty($docLocale)) {

            $docCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;

            if (str_contains($docLocale, '_')) {
                $parts = explode('_', $docLocale);
                if (!empty($parts[1])) {
                    $docCountryIso = $parts[1];
                }
            }

            $realLang = explode('_', $docLocale);
            $docRealLanguageIso = $realLang[0];
            $hrefLang = strtolower($docRealLanguageIso);
            if (!empty($docCountryIso) && $docCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                $hrefLang .= '-' . strtolower($docCountryIso);
            }
        }

        // domain has locale property, so there are no subpages
        $isRootDomain = !empty($docLocale);

        // if it's root domain, check if it's allowed for active locales
        if ($isRootDomain === true && !in_array($docLocale, array_column($zone->getActiveLocales(), 'locale'), true)) {
            return null;
        }

        // do not render sub pages if current domain is root domain
        $subPages = $isRootDomain === true ? [] : $this->createSubSites($domainDoc, $pimcoreSite, $zone, $routeItem, $siteRequestContext, $fullBootstrap);

        return new ZoneSite(
            $siteRequestContext,
            $pimcoreSite,
            $rootId,
            $isRootDomain,
            $docLocale === $routeItemLocale,
            $docLocale,
            $docCountryIso,
            $docRealLanguageIso,
            $hrefLang,
            null,
            $siteRequestContext->getDomainUrl(),
            $siteRequestContext->getDomainUrl(),
            $domainDoc->getRealFullPath(),
            $domainDoc->getRealFullPath(),
            $domainDoc->getType(),
            $subPages
        );
    }

    protected function createSubSites(
        Document $domainDoc,
        ?Site $pimcoreSite,
        ZoneInterface $zone,
        RouteItemInterface $routeItem,
        SiteRequestContext $siteRequestContext,
        bool $fullBootstrap
    ): array {

        $subPages = [];
        $processedChildLocales = [];

        $routeItemLocale = $routeItem->getLocaleFragment();

        foreach ($domainDoc->getChildren(true) as $child) {

            $validPath = true;
            $loopDetector = [];
            $childCountryIso = null;
            $urlKey = $child->getKey();
            $docUrl = $urlKey;
            $childDocLocale = $child->getProperty('language');

            if (!in_array($child->getType(), ['page', 'hardlink', 'link'], true)) {
                continue;
            }

            // we're only booting a specific locale. skip other subpage rendering if specific has been found!
            if ($fullBootstrap === false && in_array($routeItemLocale, $processedChildLocales, true)) {
                return $subPages;
            }

            // we're only booting a specific locale. skip other subpage but parse only requested one!
            if ($fullBootstrap === false && $routeItemLocale !== $childDocLocale) {
                continue;
            }

            //detect real doc url: if page is a link, move to target until we found a real document.
            if ($child->getType() === 'link') {
                /** @var Document\Link $linkChild */
                $linkChild = $child;
                while ($linkChild instanceof Document\Link) {
                    if (in_array($linkChild->getPath(), $loopDetector, true)) {
                        $validPath = false;

                        break;
                    }

                    if ($linkChild->getLinktype() !== 'internal') {
                        $validPath = false;

                        break;
                    }

                    if ($linkChild->getInternalType() !== 'document') {
                        $validPath = false;

                        break;
                    }

                    $loopDetector[] = $linkChild->getPath();
                    $linkChild = Document::getById($linkChild->getInternal());

                    if (!$linkChild instanceof Document) {
                        $validPath = false;

                        break;
                    }

                    $isPublishedMode = $linkChild->isPublished() === true || $routeItem->isFrontendRequestByAdmin();
                    if ($isPublishedMode === false) {
                        $validPath = false;

                        break;
                    }

                    // we can't use getFullPath since i18n will transform the path since it could be a "out-of-context" link.
                    $docUrl = ltrim($linkChild->getPath(), DIRECTORY_SEPARATOR) . $linkChild->getKey();
                }
            }

            $isPublishedMode = $child->isPublished() === true || $routeItem->isFrontendRequestByAdmin();
            if ($validPath === false || $isPublishedMode === false) {
                continue;
            }

            $childCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;

            if (str_contains($childDocLocale, '_')) {
                $parts = explode('_', $childDocLocale);
                if (!empty($parts[1])) {
                    $childCountryIso = $parts[1];
                }
            }

            if (empty($childDocLocale) || !in_array($childDocLocale, array_column($zone->getActiveLocales(), 'locale'), true)) {
                continue;
            }

            $processedChildLocales[] = $childDocLocale;

            $domainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $urlKey, DIRECTORY_SEPARATOR);
            $homeDomainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $docUrl, DIRECTORY_SEPARATOR);

            $realLang = explode('_', $childDocLocale);
            $hrefLang = strtolower($realLang[0]);
            if (!empty($childCountryIso) && $childCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                $hrefLang .= '-' . strtolower($childCountryIso);
            }

            $subPages[] = new ZoneSite(
                $siteRequestContext,
                $pimcoreSite,
                $child->getId(),
                false,
                $routeItemLocale === $childDocLocale,
                $childDocLocale,
                $childCountryIso,
                $realLang[0],
                $hrefLang,
                $urlKey,
                $domainUrlWithKey,
                $homeDomainUrlWithKey,
                $domainDoc->getRealFullPath(),
                $child->getRealFullPath(),
                $child->getType()
            );
        }

        return $subPages;
    }

    protected function generateSiteRequestContext(string $domain, mixed $domainConfiguration): SiteRequestContext
    {
        $defaultScheme = $this->configuration->getConfig('request_scheme');
        $defaultPort = $this->configuration->getConfig('request_port');

        $httpScheme = is_array($domainConfiguration) ? $domainConfiguration[1] : $defaultScheme;
        $httpPort = is_array($domainConfiguration) ? $domainConfiguration[2] : $defaultPort;
        $httpsPort = is_array($domainConfiguration) ? $domainConfiguration[2] : ($defaultScheme === 'http' ? 443 : $defaultPort);

        $domainUrl = $domain;

        $domainPort = null;
        if ($httpScheme === 'http' && $httpPort !== 80) {
            $domainPort = $httpPort;
        } elseif ($httpScheme === 'https' && $httpPort !== 443) {
            $domainPort = $httpsPort;
        }

        if (!str_starts_with($domainUrl, 'http')) {
            $domainUrl = sprintf('%s://%s', $httpScheme, $domainUrl);;
        }

        if ($domainPort !== null) {
            $domainUrl = sprintf('%s:%d', $domainUrl, $domainPort);
        }

        return new SiteRequestContext(
            $httpScheme,
            $httpPort,
            $httpsPort,
            rtrim($domainUrl, DIRECTORY_SEPARATOR),
            $domain,
            preg_replace('/^www./', '', $domain)
        );
    }

    protected function fetchAvailablePimcoreSites(): array
    {
        return $this->db->fetchAllAssociative('SELECT `id`, `mainDomain`, `rootId` FROM sites');
    }

    protected function buildVirtualZoneSite(): array
    {
        $hostUrl = !empty($this->generalDomain) && $this->generalDomain !== 'localhost' ? $this->generalDomain : \Pimcore\Tool::getHostUrl();
        $realHostUrl = str_contains($hostUrl, 'http') ? parse_url($hostUrl, PHP_URL_HOST) : $hostUrl;

        return [
            'mainDomain' => $realHostUrl ?? '',
            'rootId'     => 1
        ];
    }

}
