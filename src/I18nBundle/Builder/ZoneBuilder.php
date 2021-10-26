<?php

namespace I18nBundle\Builder;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;
use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Model\I18nSite;
use I18nBundle\Model\I18nSiteInterface;
use I18nBundle\Model\I18nZone;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Registry\LocaleProviderRegistry;
use I18nBundle\Registry\PathGeneratorRegistry;
use Pimcore\Db\Connection;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZoneBuilder
{
    protected ?string $generalDomain;
    protected Connection $db;
    protected Configuration $configuration;
    protected ContextBuilder $contextBuilder;
    protected LocaleProviderRegistry $localeProviderRegistry;
    protected PathGeneratorRegistry $pathGeneratorRegistry;

    public function __construct(
        ?string $generalDomain,
        Connection $db,
        Configuration $configuration,
        ContextBuilder $contextBuilder,
        LocaleProviderRegistry $localeProviderRegistry,
        PathGeneratorRegistry $pathGeneratorRegistry
    ) {
        $this->db = $db;
        $this->generalDomain = $generalDomain;
        $this->configuration = $configuration;
        $this->contextBuilder = $contextBuilder;
        $this->localeProviderRegistry = $localeProviderRegistry;
        $this->pathGeneratorRegistry = $pathGeneratorRegistry;
    }

    public function buildZone(array $contextOptions)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'site'                         => null,
                'request_source'               => null,
                'base_locale'                  => null,
                'path_generator_options'       => null,
                'edit_mode'                    => null,
                'is_frontend_request_by_admin' => null,
            ])
            ->setAllowedTypes('site', ['null', Site::class])
            ->setAllowedTypes('request_source', ['string'])
            ->setAllowedTypes('base_locale', ['string'])
            ->setAllowedTypes('path_generator_options', ['array'])
            ->setAllowedTypes('edit_mode', ['bool'])
            ->setAllowedTypes('is_frontend_request_by_admin', ['bool'])
            ->setAllowedValues('request_source', ['static_route', 'document', 'symfony']);

        $options = $optionsResolver->resolve($contextOptions);

        return $this->build($options);
    }

    protected function build(array $options): I18nZoneInterface
    {
        $site = $options['site'];

        $zones = $this->configuration->getConfig('zones');

        // no zones defined
        if (empty($zones)) {
            return $this->createZone($options, $this->configuration->getConfigNode());
        }

        // it's not a site request, zones are invalid. use the default settings.
        if (!$site instanceof Site) {
            return $this->createZone($options, $this->configuration->getConfigNode());
        }

        $validZone = false;
        $zoneConfig = [];
        $currentSite = $site;

        foreach ($zones as $zone) {
            if (in_array($currentSite->getMainDomain(), $zone['domains'], true)) {
                $validZone = true;
                $zoneConfig = $zone;

                break;
            }
        }

        // no valid zone found. use default one.
        if ($validZone === false) {
            return $this->createZone($options, $this->configuration->getConfigNode());
        }

        return $this->createZone($options, $zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name'], $zoneConfig['domains']);
    }

    protected function createZone(
        array $options,
        array $config,
        ?int $currentZoneId = null,
        ?string $currentZoneName = null,
        array $currentZoneDomains = []
    ): I18nZoneInterface {

        if (!empty($config['locale_adapter']) && !$this->localeProviderRegistry->has($config['locale_adapter'])) {
            throw new \Exception(sprintf(
                'locale provider "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                $config['locale_adapter'],
                'i18n.adapter.locale',
                $config['locale']
            ));
        }

        $baseLocale = $options['base_locale'];
        $requestSource = $options['request_source'];
        $pathGeneratorOptions = $options['path_generator_options'];

        $localeProvider = $this->localeProviderRegistry->get($config['locale_adapter']);
        $localeProvider->setCurrentZoneConfig($currentZoneId, $this->filterZoneConfiguration($config));

        $context = $this->contextBuilder->build($baseLocale, $localeProvider, $config['mode']);
        $pathGenerator = $this->buildPathGenerator($requestSource, $pathGeneratorOptions);
        $zoneSites = $this->createZoneSites($options, $config, $currentZoneId, $currentZoneDomains, $localeProvider);

        return new I18nZone(
            $currentZoneId,
            $currentZoneName,
            $currentZoneDomains,
            $config['mode'],
            $config['translations'],
            $context,
            $localeProvider,
            $pathGenerator,
            $zoneSites,
        );
    }

    protected function createZoneSites(array $options, array $config, ?int $currentZoneId, array $currentZoneDomains, LocaleProviderInterface $localeProvider): array
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
            $zoneSite = $this->createZoneSite($options, $config, $localeProvider, $currentZoneId, $currentZoneDomains, $site['mainDomain'], $site['rootId']);
            if ($zoneSite instanceof I18nSiteInterface) {
                $zoneSites[] = $zoneSite;
            }
        }

        return $zoneSites;
    }

    protected function createZoneSite(
        array $options,
        array $config,
        LocaleProviderInterface $localeProvider,
        ?int $currentZoneId,
        array $currentZoneDomains,
        string $domain,
        int $rootId
    ): ?I18nSiteInterface {
        $domainDoc = Document::getById($rootId);

        if (!$domainDoc instanceof Document) {
            return null;
        }

        $domainHost = $this->getDomainHost($domain);
        $isFrontendRequestByAdmin = $options['is_frontend_request_by_admin'];

        $valid = $currentZoneId === null;

        if ($currentZoneId !== null && !empty($currentZoneDomains)) {
            foreach ($currentZoneDomains as $currentZoneDomain) {
                if ($domainHost === $this->getDomainHost($currentZoneDomain)) {
                    $valid = true;

                    break;
                }
            }
        }

        $isPublishedMode = $domainDoc->isPublished() === true || $isFrontendRequestByAdmin;
        if ($valid === false || $isPublishedMode === false) {
            return null;
        }

        $isRootDomain = false;
        $subPages = [];

        $docLocale = $domainDoc->getProperty('language');
        $docCountryIso = null;

        if ($config['mode'] === 'country' && !empty($docLocale)) {
            $docCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        if (str_contains($docLocale, '_')) {
            $parts = explode('_', $docLocale);
            if (isset($parts[1]) && !empty($parts[1])) {
                $docCountryIso = $parts[1];
            }
        }

        $activeLocales = $localeProvider->getActiveLocales();

        //domain has language, it's the root.
        if (!empty($docLocale)) {
            $isRootDomain = true;
            if (!in_array($docLocale, array_column($activeLocales, 'locale'), true)) {
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

                if ($config['mode'] === 'country') {
                    $childCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
                }

                if (str_contains($childDocLocale, '_')) {
                    $parts = explode('_', $childDocLocale);
                    if (isset($parts[1]) && !empty($parts[1])) {
                        $childCountryIso = $parts[1];
                    }
                }

                if (empty($childDocLocale) || !in_array($childDocLocale, array_column($activeLocales, 'locale'), true)) {
                    continue;
                }

                $domainUrl = $this->getDomainUrl($domain);
                $domainUrlWithKey = rtrim($domainUrl . DIRECTORY_SEPARATOR . $urlKey, DIRECTORY_SEPARATOR);
                $homeDomainUrlWithKey = rtrim($domainUrl . DIRECTORY_SEPARATOR . $docUrl, DIRECTORY_SEPARATOR);

                $realLang = explode('_', $childDocLocale);
                $hrefLang = strtolower($realLang[0]);
                if (!empty($childCountryIso) && $childCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    $hrefLang .= '-' . strtolower($childCountryIso);
                }

                $subPages[] = new I18nSite(
                    $child->getId(),
                    $domain,
                    $domainHost,
                    false,
                    $childDocLocale,
                    $childCountryIso,
                    $realLang[0],
                    $hrefLang,
                    $urlKey,
                    $domainUrlWithKey,
                    $homeDomainUrlWithKey,
                    $domainUrl,
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

        $domainUrl = $this->getDomainUrl($domain);

        return new I18nSite(
            $rootId,
            $domain,
            $domainHost,
            $isRootDomain,
            $docLocale,
            $docCountryIso,
            $docRealLanguageIso,
            $hrefLang,
            null,
            $domainUrl,
            $domainUrl,
            $domainUrl,
            $domainDoc->getRealFullPath(),
            $domainDoc->getType(),
            $subPages
        );
    }

    public function buildPathGenerator(?string $pathGeneratorIdentifier, array $pathGeneratorOptions): PathGeneratorInterface
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

        $pathGenerator = $this->pathGeneratorRegistry->get($pathGeneratorIdentifier);

        $pathGeneratorOptionsResolver = new OptionsResolver();
        $pathGenerator->configureOptions($pathGeneratorOptionsResolver);
        $pathGenerator->setOptions($pathGeneratorOptionsResolver->resolve($pathGeneratorOptions));

        return $pathGenerator;
    }

    /**
     * Get Domain Url of given domain based on current request scheme!
     */
    protected function getDomainUrl(string $domain): string
    {
        $scheme = \Pimcore\Tool::getRequestScheme();
        $domainHost = $this->getDomainHost($domain, false);
        $domainPort = $this->getDomainPort($domain);
        $domainUrl = $domainHost;

        if (!str_contains($domainUrl, 'http:')) {
            $domainUrl = $scheme . '://' . $domainUrl;
        }

        if (!empty($domainPort)) {
            $domainUrl .= ':' . $domainPort;
        }

        return rtrim($domainUrl, DIRECTORY_SEPARATOR);
    }

    protected function getDomainHost(string $domain, bool $stripWWW = true): string
    {
        $urlInfo = parse_url($domain);
        $host = $urlInfo['host'] ?? $urlInfo['path'];

        return $stripWWW ? preg_replace('/^www./', '', $host) : $host;
    }

    protected function getDomainPort(string $domain): string
    {
        $port = '';
        $urlInfo = parse_url($domain);
        if (isset($urlInfo['port']) && $urlInfo['port'] !== 80) {
            $port = $urlInfo['port'];
        }

        return $port;
    }

    protected function filterZoneConfiguration(array $config): array
    {
        $blackList = ['zones', 'mode', 'locale_adapter'];

        return array_diff_key($config, array_flip($blackList));
    }

    protected function fetchAvailableSites(): array
    {
        return $this->db->fetchAllAssociative('SELECT `mainDomain`, `rootId` FROM sites');
    }

}
