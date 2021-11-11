<?php

namespace I18nBundle\Builder;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Model\ZoneSite;
use I18nBundle\Model\ZoneSiteInterface;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\SiteRequestContext;
use Pimcore\Db\Connection;
use Pimcore\Model\Document;

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

    public function buildZoneSites(ZoneInterface $zone, bool $isFrontendRequestByAdmin = false): array
    {
        $zoneSites = [];
        $availableSites = $this->fetchAvailableSites();

        //it's a simple page, no sites: create a default one
        if (count($availableSites) === 0) {

            $hostUrl = !empty($this->generalDomain) && $this->generalDomain !== 'localhost' ? $this->generalDomain : \Pimcore\Tool::getHostUrl();
            $realHostUrl = parse_url($hostUrl, PHP_URL_HOST);

            $availableSites[] = [
                'mainDomain' => $realHostUrl ?? '',
                'rootId'     => 1
            ];
        }

        foreach ($availableSites as $site) {
            $zoneSite = $this->createZoneSite($zone, $isFrontendRequestByAdmin, $site['mainDomain'], $site['rootId']);
            if ($zoneSite instanceof ZoneSiteInterface) {
                $zoneSites[] = $zoneSite;
            }
        }

        return $zoneSites;
    }

    protected function createZoneSite(
        ZoneInterface $zone,
        bool $isFrontendRequestByAdmin,
        string $mainDomain,
        int $rootId
    ): ?ZoneSiteInterface {

        $domainDoc = Document::getById($rootId);

        if (!$domainDoc instanceof Document) {
            return null;
        }

        $currentZoneDomainConfiguration = null;
        $valid = $zone->getId() === null;

        if ($zone->getId() !== null && !empty($zone->getDomains())) {
            foreach ($zone->getDomains() as $currentZoneDomain) {
                $currentZoneDomainHost = is_array($currentZoneDomain) ? $currentZoneDomain[0] : $currentZoneDomain;
                if ($mainDomain === $currentZoneDomainHost) {
                    $currentZoneDomainConfiguration = $currentZoneDomain;
                    $valid = true;

                    break;
                }
            }
        }

        $isPublishedMode = $domainDoc->isPublished() === true || $isFrontendRequestByAdmin;
        if ($valid === false || $isPublishedMode === false) {
            return null;
        }

        $siteRequestContext = $this->generateSiteRequestContext($mainDomain, $currentZoneDomainConfiguration);

        $isRootDomain = false;
        $subPages = [];

        $docLocale = $domainDoc->getProperty('language');
        $docCountryIso = null;

        if ($zone->getMode() === 'country' && !empty($docLocale)) {
            $docCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        if (str_contains($docLocale, '_')) {
            $parts = explode('_', $docLocale);
            if (isset($parts[1]) && !empty($parts[1])) {
                $docCountryIso = $parts[1];
            }
        }

        //domain has language, it's the root.
        if (!empty($docLocale)) {
            $isRootDomain = true;
            if (!in_array($docLocale, array_column($zone->getActiveLocales(), 'locale'), true)) {
                return null;
            }
        } else {
            $children = $domainDoc->getChildren(true);

            foreach ($children as $child) {

                if (!in_array($child->getType(), ['page', 'hardlink', 'link'], true)) {
                    continue;
                }

                $urlKey = $child->getKey();
                $docUrl = $urlKey;
                $validPath = true;
                $loopDetector = [];

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

                        $isPublishedMode = $linkChild->isPublished() === true || $isFrontendRequestByAdmin;
                        if ($isPublishedMode === false) {
                            $validPath = false;

                            break;
                        }

                        // we can't use getFullPath since i18n will transform the path since it could be a "out-of-context" link.
                        $docUrl = ltrim($linkChild->getPath(), DIRECTORY_SEPARATOR) . $linkChild->getKey();
                    }
                }

                $isPublishedMode = $child->isPublished() === true || $isFrontendRequestByAdmin;
                if ($validPath === false || $isPublishedMode === false) {
                    continue;
                }

                $childDocLocale = $child->getProperty('language');
                $childCountryIso = null;

                if ($zone->getMode() === 'country') {
                    $childCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
                }

                if (str_contains($childDocLocale, '_')) {
                    $parts = explode('_', $childDocLocale);
                    if (isset($parts[1]) && !empty($parts[1])) {
                        $childCountryIso = $parts[1];
                    }
                }

                if (empty($childDocLocale) || !in_array($childDocLocale, array_column($zone->getActiveLocales(), 'locale'), true)) {
                    continue;
                }

                $domainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $urlKey, DIRECTORY_SEPARATOR);
                $homeDomainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $docUrl, DIRECTORY_SEPARATOR);

                $realLang = explode('_', $childDocLocale);
                $hrefLang = strtolower($realLang[0]);
                if (!empty($childCountryIso) && $childCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    $hrefLang .= '-' . strtolower($childCountryIso);
                }

                $subPages[] = new ZoneSite(
                    $siteRequestContext,
                    $child->getId(),
                    false,
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
        }

        $hrefLang = '';
        $docRealLanguageIso = '';

        if (!empty($docLocale)) {
            $realLang = explode('_', $docLocale);
            $docRealLanguageIso = $realLang[0];
            $hrefLang = strtolower($docRealLanguageIso);
            if (!empty($docCountryIso) && $docCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                $hrefLang .= '-' . strtolower($docCountryIso);
            }
        }

        return new ZoneSite(
            $siteRequestContext,
            $rootId,
            $isRootDomain,
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

    protected function fetchAvailableSites(): array
    {
        return $this->db->fetchAllAssociative('SELECT `mainDomain`, `rootId` FROM sites');
    }
}
