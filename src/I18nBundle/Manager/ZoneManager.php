<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Country\AbstractCountry;
use I18nBundle\Adapter\Language\AbstractLanguage;
use I18nBundle\Adapter\Language\LanguageInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Adapter\Country\CountryInterface;
use I18nBundle\Definitions;
use I18nBundle\Registry\CountryRegistry;
use I18nBundle\Registry\LanguageRegistry;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;

class ZoneManager
{
    /**
     * @var SiteResolver
     */
    private $siteResolver;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var LanguageRegistry
     */
    protected $languageRegistry;

    /**
     * @var CountryRegistry
     */
    protected $countryRegistry;

    /**
     * Stores the current Zone info
     *
     * @var
     */
    protected $currentZone = NULL;

    /**
     * Stores the current Zone domains
     *
     * @var
     */
    protected $currentZoneDomains = NULL;

    /**
     * @var bool
     */
    protected $isInZone = FALSE;

    /**
     * ZoneManager constructor.
     *
     * @param SiteResolver     $siteResolver
     * @param Configuration    $configuration
     * @param LanguageRegistry $languageRegistry
     * @param CountryRegistry  $countryRegistry
     */
    public function __construct(
        SiteResolver $siteResolver,
        Configuration $configuration,
        LanguageRegistry $languageRegistry,
        CountryRegistry $countryRegistry
    ) {
        $this->siteResolver = $siteResolver;
        $this->configuration = $configuration;
        $this->languageRegistry = $languageRegistry;
        $this->countryRegistry = $countryRegistry;
    }

    /**
     * @throws \Exception
     */
    public function initZones()
    {
        if (!empty($this->currentZone)) {
            return;
        }

        $zones = $this->configuration->getConfig('zones');

        //no zones defined
        if (empty($zones)) {
            $this->currentZone = $this->mapData($this->configuration->getConfigNode());
        } else {

            //it's not a site request, zones are invalid. use the default settings.
            if ($this->siteResolver->isSiteRequest() === FALSE) {
                $this->currentZone = $this->mapData($this->configuration->getConfigNode());
            } else {

                $validZone = FALSE;
                $zoneConfig = [];
                $currentSite = $this->siteResolver->getSite();

                foreach ($zones as $zone) {
                    if (in_array($currentSite->getMainDomain(), $zone['domains'])) {
                        $validZone = TRUE;
                        $zoneConfig = $zone;
                        break;
                    }
                }

                //no valid zone found. use default one.
                if ($validZone === FALSE) {
                    $this->currentZone = $this->mapData($this->configuration->getConfigNode());
                } else {

                    $this->isInZone = TRUE;
                    $parsedZoneConfig = $this->mapData($zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name']);
                    $parsedZoneConfig['valid_domains'] = $zoneConfig['domains'];

                    $this->currentZone = $parsedZoneConfig;
                }
            }
        }

        $this->setupZoneDomains();
    }

    /**
     * @return null
     */
    private function setupZoneDomains()
    {
        if (!is_null($this->currentZoneDomains)) {
            return $this->currentZoneDomains;
        }

        $db = \Pimcore\Db::get();
        $availableSites = $db->fetchAll('SELECT * FROM sites');

        $zoneDomains = [];

        //it's a simple page, no sites.
        if (empty($availableSites)) {
            $zoneDomains[] = $this->mapDomainData(\Pimcore\Tool::getHostUrl(), 1);
        } else {
            foreach ($availableSites as $site) {
                $domainInfo = $this->mapDomainData($site['mainDomain'], $site['rootId']);
                if ($domainInfo !== FALSE) {
                    $zoneDomains[] = $domainInfo;
                }
            }
        }

        $this->addLocaleUrlMappingToConfig($zoneDomains);
        $this->currentZoneDomains = $zoneDomains;
    }

    /**
     * @param null $slot
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentZoneInfo($slot = NULL)
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        if (!array_key_exists($slot, $this->currentZone)) {
            throw new \Exception(sprintf('current zone config slot "%s" is not defined', $slot));
        }

        return $this->currentZone[$slot];
    }

    /**
     * @param bool $flatten
     *
     * @return array|null
     */
    public function getCurrentZoneDomains($flatten = FALSE)
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        return $flatten ? $this->flattenDomainTree($this->currentZoneDomains) : $this->currentZoneDomains;
    }

    /**
     * @return LanguageInterface
     * @throws \Exception
     */
    public function getCurrentZoneLanguageAdapter()
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        if (!$this->currentZone['language_adapter'] instanceof LanguageInterface) {
            throw new \Exception(sprintf('language adapter is invalid. given language adapter is "%s"', get_class($this->currentZone['language_adapter'])));
        }

        return $this->currentZone['language_adapter'];
    }

    /**
     * @return CountryInterface
     * @throws \Exception
     */
    public function getCurrentZoneCountryAdapter()
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        if ($this->getCurrentZoneInfo('mode') !== 'country') {
            throw new \Exception(sprintf('current i18n mode is "%s" and does not support country adapter.', $this->getCurrentZoneInfo('mode')));
        }

        if (!$this->currentZone['country_adapter'] instanceof CountryInterface) {
            throw new \Exception(sprintf('country adapter is invalid. given country adapter is "%s"', get_class($this->currentZone['country_adapter'])));
        }

        return $this->currentZone['country_adapter'];
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isInZone()
    {
        return $this->isInZone;
    }

    /**
     * @param array       $config
     * @param null|int    $zoneId
     * @param null|string $zoneName
     *
     * @return array
     * @throws \Exception
     */
    private function mapData($config, $zoneId = NULL, $zoneName = NULL)
    {
        if (!empty($config['country_adapter']) && !$this->countryRegistry->has($config['country_adapter'])) {
            throw new \Exception(sprintf(
                    'country adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                    $config['country_adapter'], 'i18n.adapter.country', $config['country_adapter'])
            );
        }

        /** @var AbstractLanguage $countryAdapter */
        $languageAdapter = $this->languageRegistry->has($config['language_adapter'])
            ? $this->languageRegistry->get($config['language_adapter'])
            : NULL;

        if (!is_null($languageAdapter)) {
            $languageAdapter->setCurrentZoneConfig($zoneId, $this->setZoneConfiguration($config));
        }

        /** @var AbstractCountry $countryAdapter */
        $countryAdapter = $this->countryRegistry->has($config['country_adapter'])
            ? $this->countryRegistry->get($config['country_adapter'])
            : NULL;

        if (!is_null($countryAdapter)) {
            $countryAdapter->setCurrentZoneConfig($zoneId, $this->setZoneConfiguration($config));
        }

        $mapData = $this->currentZone = [
            'zoneId'           => $zoneId,
            'zoneName'         => $zoneName,
            'mode'             => $config['mode'],
            'translations'     => $config['translations'],
            'language_adapter' => $languageAdapter,
            'country_adapter'  => $countryAdapter
        ];

        return $mapData;
    }

    /**
     * @param $domain
     * @param $rootId
     * @return array|bool
     */
    private function mapDomainData($domain, $rootId)
    {
        $domainHost = $this->getDomainHost($domain);
        $domainDoc = Document::getById($rootId);

        $valid = FALSE;

        if ($this->isInZone() && !empty($this->getCurrentZoneInfo('valid_domains'))) {
            $validDomains = $this->getCurrentZoneInfo('valid_domains');
            foreach ($validDomains as $validDomain) {
                if ($domainHost === $this->getDomainHost($validDomain)) {
                    $valid = TRUE;
                    break;
                }
            }
        } else {
            $valid = TRUE;
        }

        if ($valid === FALSE || $domainDoc->isPublished() === FALSE) {
            return FALSE;
        }

        $isRootDomain = FALSE;
        $subPages = FALSE;

        $docLanguageIso = $domainDoc->getProperty('language');
        $docCountryIso = $domainDoc->getProperty('country');

        $country = NULL;
        $language = NULL;

        $validCountries = [];
        $validLanguages = $this->getCurrentZoneLanguageAdapter()->getActiveLanguages();

        if ($this->getCurrentZoneInfo('mode') === 'country') {
            $validCountries = $this->getCurrentZoneCountryAdapter()->getActiveCountries();
            if (!empty($docCountryIso) && array_search($docCountryIso, array_column($validCountries, 'isoCode')) === FALSE) {
                return FALSE;
            }
        }

        //domain has language, it's the root.
        if (!empty($docLanguageIso)) {
            $isRootDomain = TRUE;
            if (array_search($docLanguageIso, array_column($validLanguages, 'isoCode')) === FALSE) {
                return FALSE;
            }
        } else {
            $children = $domainDoc->getChildren();

            /** @var Document $child */
            foreach ($children as $child) {

                if (!in_array($child->getType(), ['page', 'hardlink', 'link'])) {
                    continue;
                }

                $urlKey = $child->getKey();
                $docUrl = $urlKey;
                $validPath = TRUE;
                $loopDetector = [];

                //if page is link, move to target page.
                if ($child->getType() === 'link') {

                    while ($child->getType() === 'link') {

                        if (in_array($child->getPath(), $loopDetector)) {
                            $validPath = FALSE;
                            break;
                        }

                        if ($child->getLinktype() !== 'internal') {
                            $validPath = FALSE;
                            break;
                        } elseif ($child->getInternalType() !== 'document') {
                            $validPath = FALSE;
                            break;
                        }

                        $loopDetector[] = $child->getPath();
                        $child = Document::getById($child->getInternal());
                        if (!$child instanceof Document || !$child->isPublished()) {
                            $validPath = FALSE;
                            break;
                        }

                        // we can't use getFullPath since i18n will transform the path since it could be a "out-of-context" link.
                        $docUrl = ltrim($child->getPath(), DIRECTORY_SEPARATOR) . $child->getKey();
                    }
                }

                if ($validPath === FALSE || !$child->isPublished()) {
                    continue;
                }

                $childLanguageIso = $child->getProperty('language');
                $childCountryIso = $child->getProperty('country');

                if (empty($childLanguageIso) || array_search($childLanguageIso, array_column($validLanguages, 'isoCode')) === FALSE) {
                    continue;
                }

                if (!empty($childCountryIso) && array_search($childCountryIso, array_column($validCountries, 'isoCode')) === FALSE) {
                    continue;
                }

                $domainUrl = $this->getDomainUrl($domain);
                $domainUrlWithKey = rtrim($domainUrl . DIRECTORY_SEPARATOR . $urlKey, DIRECTORY_SEPARATOR);
                $homeDomainUrlWithKey = rtrim($domainUrl . DIRECTORY_SEPARATOR . $docUrl, DIRECTORY_SEPARATOR);

                $realLang = explode('_', $childLanguageIso);
                $hrefLang = strtolower($realLang[0]);
                if (!empty($childCountryIso) && $childCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    $hrefLang .= '-' . strtolower($childCountryIso);
                }

                $subPages[] = [
                    'id'               => $child->getId(),
                    'host'             => $domain,
                    'realHost'         => $domainHost,
                    'locale'           => $childLanguageIso,
                    'countryIso'       => $childCountryIso,
                    'languageIso'      => $realLang[0],
                    'hrefLang'         => $hrefLang,
                    'localeUrlMapping' => $urlKey,
                    'url'              => $domainUrlWithKey,
                    'homeUrl'          => $homeDomainUrlWithKey,
                    'domainUrl'        => $domainUrl,
                    'fullPath'         => $child->getRealFullPath(),
                    'type'             => $child->getType()
                ];
            }
        }

        $hrefLang = '';
        $docRealLanguageIso = '';
        if (!empty($docLanguageIso)) {
            $realLang = explode('_', $docLanguageIso);
            $docRealLanguageIso = $realLang[0];
            $hrefLang = strtolower($docRealLanguageIso);
            if (!empty($docCountryIso) && $docCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                $hrefLang .= '-' . strtolower($docCountryIso);
            }
        }

        $domainUrl = $this->getDomainUrl($domain);

        $domainData = [
            'id'               => $rootId,
            'host'             => $domain,
            'realHost'         => $domainHost,
            'isRootDomain'     => $isRootDomain,
            'locale'           => $docLanguageIso,
            'countryIso'       => $docCountryIso,
            'languageIso'      => $docRealLanguageIso,
            'hrefLang'         => $hrefLang,
            'localeUrlMapping' => NULL,
            'url'              => $domainUrl,
            'homeUrl'          => $domainUrl,
            'domainUrl'        => $domainUrl,
            'fullPath'         => $domainDoc->getRealFullPath(),
            'type'             => $domainDoc->getType(),
            'subPages'         => $subPages
        ];

        return $domainData;
    }

    /**
     * @param array $zoneDomains
     */
    private function addLocaleUrlMappingToConfig($zoneDomains = [])
    {
        $localeUrlMapping = [];
        $domains = $this->flattenDomainTree($zoneDomains);

        foreach ($domains as $domain) {
            if (!empty($domain['locale'])) {
                $localeUrlMapping[$domain['locale']] = $domain['localeUrlMapping'];
            }
        }

        $this->currentZone['locale_url_mapping'] = $localeUrlMapping;

    }

    /**
     * Get Domain Url of given domain based on current request scheme!
     *
     * @param $domain
     * @return string
     */
    private function getDomainUrl($domain)
    {
        $scheme = \Pimcore\Tool::getRequestScheme();
        $domainHost = $this->getDomainHost($domain);
        $domainPort = $this->getDomainPort($domain);
        $domainUrl = $domainHost;

        if (strpos($domainUrl, 'http:') === FALSE) {
            $domainUrl = $scheme . '://' . $domainUrl;
        }

        if (!empty($domainPort)) {
            $domainUrl = $domainUrl . ':' . $domainPort;
        }

        return rtrim($domainUrl, DIRECTORY_SEPARATOR);
    }

    private function getDomainHost($domain)
    {
        $urlInfo = parse_url($domain);
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : $urlInfo['path'];
        $host = preg_replace('/^www./', '', $host);

        return $host;
    }

    private function getDomainPort($domain)
    {
        $port = '';
        $urlInfo = parse_url($domain);
        if (isset($urlInfo['port']) && $urlInfo['port'] !== 80) {
            $port = $urlInfo['port'];
        }

        return $port;
    }

    private function flattenDomainTree($zoneDomains)
    {
        $elements = [];
        foreach ($zoneDomains as $domain) {
            if (!empty($domain['subPages'])) {
                foreach ($domain['subPages'] as $subPage) {
                    $elements[] = $subPage;
                }
            }
            //remove sub pages now
            unset($domain['subPages']);
            if (empty($domain['countryIso']) && empty($domain['languageIso'])) {
                continue;
            }
            $elements[] = $domain;
        }

        return $elements;
    }

    /**
     * create config array for adapter classes
     *
     * @param $config
     *
     * @return array
     */
    private function setZoneConfiguration($config)
    {
        $blackList = ['zones', 'mode', 'language_adapter', 'country_adapter'];
        $validConfig = array_diff_key($config, array_flip($blackList));

        return $validConfig;
    }

}