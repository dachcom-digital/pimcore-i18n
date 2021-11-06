<?php

namespace I18nBundle\Builder;

use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Model\I18nZoneSite;
use I18nBundle\Model\I18nZoneSiteInterface;
use I18nBundle\Model\I18nZone;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Model\SiteRequestContext;
use I18nBundle\Registry\LocaleProviderRegistry;
use I18nBundle\Registry\PathGeneratorRegistry;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Db\Connection;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZoneBuilder
{
    protected ?string $generalDomain;
    protected Connection $db;
    protected Configuration $configuration;
    protected LocaleProviderRegistry $localeProviderRegistry;
    protected PathGeneratorRegistry $pathGeneratorRegistry;

    public function __construct(
        ?string $generalDomain,
        Connection $db,
        Configuration $configuration,
        LocaleProviderRegistry $localeProviderRegistry,
        PathGeneratorRegistry $pathGeneratorRegistry
    ) {
        $this->db = $db;
        $this->generalDomain = $generalDomain;
        $this->configuration = $configuration;
        $this->localeProviderRegistry = $localeProviderRegistry;
        $this->pathGeneratorRegistry = $pathGeneratorRegistry;
    }

    public function buildZone(array $contextOptions): I18nZoneInterface
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'route_item'                   => null,
                'edit_mode'                    => false,
                'is_frontend_request_by_admin' => false,
            ])
            ->setAllowedTypes('route_item', [RouteItemInterface::class])
            ->setAllowedTypes('edit_mode', ['bool'])
            ->setAllowedTypes('is_frontend_request_by_admin', ['bool']);

        $options = $optionsResolver->resolve($contextOptions);

        return $this->build($options);
    }

    protected function build(array $options): I18nZoneInterface
    {
        /** @var RouteItemInterface $routeItem */
        $routeItem = $options['route_item'];
        $site = $routeItem->getRouteContextBag()->get('site');

        $zones = $this->configuration->getConfig('zones');

        // no zones defined
        if (empty($zones)) {
            return $this->createZone($options, $this->configuration->getConfigNode());
        }

        if (!$site instanceof Site) {
            throw new \Exception('To generate a zone object, you need to assign a valid site if zones are configured. No site assignment found. Maybe there is a typo in your i18n.zones.domains mapping?');
        }

        $validZone = false;
        $zoneConfig = [];

        foreach ($zones as $zone) {

            $flattenDomains = array_map(static function ($domain) {
                return is_string($domain) ? $domain : $domain[0];
            }, $zone['domains']);

            if (in_array($site->getMainDomain(), $flattenDomains, true)) {
                $validZone = true;
                $zoneConfig = $zone;

                break;
            }
        }

        // no valid zone found. use default one.
        if ($validZone === false) {
            throw new \Exception(sprintf('No i18n zone for domain "%s" found. Maybe there is a typo in your i18n.zones.domains mapping?', $site->getMainDomain()));
        }

        return $this->createZone($options, $zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name'], $zoneConfig['domains']);
    }

    protected function createZone(
        array $options,
        array $zoneDefinition,
        ?int $currentZoneId = null,
        ?string $currentZoneName = null,
        array $currentZoneDomains = []
    ): I18nZoneInterface {

        if (!empty($zoneDefinition['locale_adapter']) && !$this->localeProviderRegistry->has($zoneDefinition['locale_adapter'])) {
            throw new \Exception(sprintf(
                'locale provider "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                $zoneDefinition['locale_adapter'],
                'i18n.adapter.locale',
                $zoneDefinition['locale']
            ));
        }

        $filteredZoneDefinition = $this->filterZoneDefinition($zoneDefinition);

        /** @var RouteItemInterface $routeItem */
        $routeItem = $options['route_item'];

        $localeProvider = $this->localeProviderRegistry->get($zoneDefinition['locale_adapter']);
        $activeZoneLocales = $localeProvider->getActiveLocales($filteredZoneDefinition);

        $pathGeneratorOptionsResolver = new OptionsResolver();
        $pathGeneratorOptionsResolver->setDefined(array_keys($routeItem->getRouteAttributes()));
        $pathGeneratorOptionsResolver->resolve($routeItem->getRouteAttributes());

        $pathGenerator = $this->buildPathGenerator($routeItem->getType());
        $pathGenerator->configureOptions($pathGeneratorOptionsResolver);

        $zoneSites = $this->createZoneSites($options, $zoneDefinition, $currentZoneId, $currentZoneDomains, $activeZoneLocales);

        return new I18nZone(
            $currentZoneId,
            $currentZoneName,
            $zoneDefinition['mode'],
            $currentZoneDomains,
            $filteredZoneDefinition,
            $routeItem,
            $localeProvider,
            $pathGenerator,
            $zoneSites,
        );
    }

    protected function createZoneSites(array $options, array $zoneDefinition, ?int $currentZoneId, array $currentZoneDomains, array $activeZoneLocales): array
    {
        $zoneSites = [];
        $availableSites = $this->fetchAvailableSites();

        //it's a simple page, no sites: create a default one
        if (count($availableSites) === 0) {
            $availableSites[] = [
                'mainDomain' => !empty($this->generalDomain) && $this->generalDomain !== 'localhost' ? $this->generalDomain : \Pimcore\Tool::getHostUrl(),
                'rootId'     => 1
            ];
        }

        foreach ($availableSites as $site) {


            $zoneSite = $this->createZoneSite($options, $zoneDefinition, $activeZoneLocales, $currentZoneId, $currentZoneDomains, $site['mainDomain'], $site['rootId']);
            if ($zoneSite instanceof I18nZoneSiteInterface) {
                $zoneSites[] = $zoneSite;
            }
        }

        return $zoneSites;
    }

    protected function createZoneSite(
        array $options,
        array $zoneDefinition,
        array $activeZoneLocales,
        ?int $currentZoneId,
        array $currentZoneDomains,
        string $mainDomain,
        int $rootId
    ): ?I18nZoneSiteInterface {

        $domainDoc = Document::getById($rootId);

        if (!$domainDoc instanceof Document) {
            return null;
        }

        $isFrontendRequestByAdmin = $options['is_frontend_request_by_admin'];

        $currentZoneDomainConfiguration = null;
        $valid = $currentZoneId === null;

        if ($currentZoneId !== null && !empty($currentZoneDomains)) {
            foreach ($currentZoneDomains as $currentZoneDomain) {
                $currentZoneDomainHost = is_array($currentZoneDomain) ? $currentZoneDomain[0] : $currentZoneDomain;
                if ($mainDomain === $currentZoneDomainHost) {
                    $currentZoneDomainConfiguration = $currentZoneDomain;
                    $valid = true;

                    break;
                }
            }
        }

        $siteRequestContext = $this->generateSiteRequestContext($mainDomain, $currentZoneDomainConfiguration);

        $isPublishedMode = $domainDoc->isPublished() === true || $isFrontendRequestByAdmin;
        if ($valid === false || $isPublishedMode === false) {
            return null;
        }

        $isRootDomain = false;
        $subPages = [];

        $docLocale = $domainDoc->getProperty('language');
        $docCountryIso = null;

        if ($zoneDefinition['mode'] === 'country' && !empty($docLocale)) {
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
            if (!in_array($docLocale, array_column($activeZoneLocales, 'locale'), true)) {
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

                if ($zoneDefinition['mode'] === 'country') {
                    $childCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
                }

                if (str_contains($childDocLocale, '_')) {
                    $parts = explode('_', $childDocLocale);
                    if (isset($parts[1]) && !empty($parts[1])) {
                        $childCountryIso = $parts[1];
                    }
                }

                if (empty($childDocLocale) || !in_array($childDocLocale, array_column($activeZoneLocales, 'locale'), true)) {
                    continue;
                }

                $domainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $urlKey, DIRECTORY_SEPARATOR);
                $homeDomainUrlWithKey = rtrim($siteRequestContext->getDomainUrl() . DIRECTORY_SEPARATOR . $docUrl, DIRECTORY_SEPARATOR);

                $realLang = explode('_', $childDocLocale);
                $hrefLang = strtolower($realLang[0]);
                if (!empty($childCountryIso) && $childCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    $hrefLang .= '-' . strtolower($childCountryIso);
                }

                $subPages[] = new I18nZoneSite(
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

        return new I18nZoneSite(
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
            $domainDoc->getType(),
            $subPages
        );
    }

    public function buildPathGenerator(?string $pathGeneratorIdentifier): PathGeneratorInterface
    {
        if (!$this->pathGeneratorRegistry->has($pathGeneratorIdentifier)) {
            throw new \Exception(
                sprintf('path.generator adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                    $pathGeneratorIdentifier,
                    'i18n.adapter.path.generator',
                    $pathGeneratorIdentifier
                )
            );
        }

        return $this->pathGeneratorRegistry->get($pathGeneratorIdentifier);
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

    protected function filterZoneDefinition(array $config): array
    {
        $blackList = ['zones', 'mode', 'locale_adapter'];

        return array_diff_key($config, array_flip($blackList));
    }

    protected function fetchAvailableSites(): array
    {
        return $this->db->fetchAllAssociative('SELECT `mainDomain`, `rootId` FROM sites');
    }
}
