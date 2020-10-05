<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Locale\LocaleInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use I18nBundle\Registry\LocaleRegistry;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Db\Connection;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

class ZoneManager
{
    /**
     * @var string|null
     */
    protected $generalDomain;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var PimcoreDocumentResolverInterface
     */
    protected $pimcoreDocumentResolver;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var LocaleRegistry
     */
    protected $localeRegistry;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * Stores the current Zone info.
     *
     * @var array
     */
    protected $currentZone = null;

    /**
     * Stores the current Zone domains.
     *
     * @var array
     */
    protected $currentZoneDomains = null;

    /**
     * @var bool
     */
    protected $isInZone = false;

    /**
     * @param string|null                      $generalDomain
     * @param Connection                       $db
     * @param RequestHelper                    $requestHelper
     * @param SiteResolver                     $siteResolver
     * @param Configuration                    $configuration
     * @param LocaleRegistry                   $localeRegistry
     * @param EditmodeResolver                 $editmodeResolver
     * @param PimcoreDocumentResolverInterface $pimcoreDocumentResolver
     */
    public function __construct(
        $generalDomain,
        Connection $db,
        RequestHelper $requestHelper,
        SiteResolver $siteResolver,
        Configuration $configuration,
        LocaleRegistry $localeRegistry,
        EditmodeResolver $editmodeResolver,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver
    ) {
        $this->db = $db;
        $this->generalDomain = $generalDomain;
        $this->requestHelper = $requestHelper;
        $this->siteResolver = $siteResolver;
        $this->configuration = $configuration;
        $this->localeRegistry = $localeRegistry;
        $this->editmodeResolver = $editmodeResolver;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
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
            $site = null;
            if (!$this->editmodeResolver->isEditmode()) {
                if ($this->siteResolver->isSiteRequest()) {
                    $site = $this->siteResolver->getSite();
                }
            } else {
                // in backend we don't have any site request, we need to fetch it via document
                $currentDocument = $this->pimcoreDocumentResolver->getDocument($this->requestHelper->getCurrentRequest());
                $site = \Pimcore\Tool\Frontend::getSiteForDocument($currentDocument);
            }

            //it's not a site request, zones are invalid. use the default settings.
            if (!$site instanceof Site) {
                $this->currentZone = $this->mapData($this->configuration->getConfigNode());
            } else {
                $validZone = false;
                $zoneConfig = [];
                $currentSite = $site;

                foreach ($zones as $zone) {
                    if (in_array($currentSite->getMainDomain(), $zone['domains'])) {
                        $validZone = true;
                        $zoneConfig = $zone;

                        break;
                    }
                }

                //no valid zone found. use default one.
                if ($validZone === false) {
                    $this->currentZone = $this->mapData($this->configuration->getConfigNode());
                } else {
                    $this->isInZone = true;
                    $parsedZoneConfig = $this->mapData($zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name']);
                    $parsedZoneConfig['valid_domains'] = $zoneConfig['domains'];

                    $this->currentZone = $parsedZoneConfig;
                }
            }
        }

        $this->setupZoneDomains();
    }

    /**
     * @throws \Exception
     */
    private function setupZoneDomains()
    {
        if (!is_null($this->currentZoneDomains)) {
            return $this->currentZoneDomains;
        }

        $availableSites = $this->db->fetchAll('SELECT * FROM sites');

        $zoneDomains = [];
        //it's a simple page, no sites.
        if (empty($availableSites)) {
            $hostUrl = !empty($this->generalDomain) && $this->generalDomain !== 'localhost' ? $this->generalDomain : \Pimcore\Tool::getHostUrl();
            $zoneDomains[] = $this->mapDomainData($hostUrl, 1);
        } else {
            foreach ($availableSites as $site) {
                $domainInfo = $this->mapDomainData($site['mainDomain'], $site['rootId']);
                if ($domainInfo !== false) {
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
     *
     * @throws \Exception
     */
    public function getCurrentZoneInfo($slot = null)
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
     *
     * @throws \Exception
     */
    public function getCurrentZoneDomains($flatten = false)
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        return $flatten ? $this->flattenDomainTree($this->currentZoneDomains) : $this->currentZoneDomains;
    }

    /**
     * @return LocaleInterface
     *
     * @throws \Exception
     */
    public function getCurrentZoneLocaleAdapter()
    {
        if (empty($this->currentZone)) {
            $this->initZones();
        }

        if (!$this->currentZone['locale_adapter'] instanceof LocaleInterface) {
            throw new \Exception(sprintf('locale adapter is invalid. given locale adapter is "%s"', get_class($this->currentZone['locale_adapter'])));
        }

        return $this->currentZone['locale_adapter'];
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isInZone()
    {
        return $this->isInZone;
    }

    /**
     * @param array  $config
     * @param int    $zoneId
     * @param string $zoneName
     *
     * @return array
     *
     * @throws \Exception
     */
    private function mapData($config, $zoneId = null, $zoneName = null)
    {
        if (!empty($config['locale_adapter']) && !$this->localeRegistry->has($config['locale_adapter'])) {
            throw new \Exception(sprintf(
                'locale adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                $config['locale_adapter'],
                'i18n.adapter.locale',
                $config['locale']
            ));
        }

        /** @var LocaleInterface $localeAdapter */
        $localeAdapter = $this->localeRegistry->has($config['locale_adapter'])
            ? $this->localeRegistry->get($config['locale_adapter'])
            : null;

        if (!is_null($localeAdapter)) {
            $localeAdapter->setCurrentZoneConfig($zoneId, $this->setZoneConfiguration($config));
        }

        $mapData = $this->currentZone = [
            'zone_id'        => $zoneId,
            'zone_name'      => $zoneName,
            'mode'           => $config['mode'],
            'translations'   => $config['translations'],
            'locale_adapter' => $localeAdapter,
        ];

        return $mapData;
    }

    /**
     * @param string $domain
     * @param int    $rootId
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    private function mapDomainData($domain, $rootId)
    {
        $domainHost = $this->getDomainHost($domain);
        $domainDoc = Document::getById($rootId);
        $isFrontendRequestByAdmin = $this->requestHelper->hasCurrentRequest() && $this->requestHelper->isFrontendRequestByAdmin();

        $valid = false;

        if ($this->isInZone() && !empty($this->getCurrentZoneInfo('valid_domains'))) {
            $validDomains = $this->getCurrentZoneInfo('valid_domains');
            foreach ($validDomains as $validDomain) {
                if ($domainHost === $this->getDomainHost($validDomain)) {
                    $valid = true;

                    break;
                }
            }
        } else {
            $valid = true;
        }

        $isPublishedMode = $domainDoc->isPublished() === true || $isFrontendRequestByAdmin;
        if ($valid === false || $isPublishedMode === false) {
            return false;
        }

        $isRootDomain = false;
        $subPages = false;

        $docLocale = $domainDoc->getProperty('language');
        $docCountryIso = null;

        if ($this->getCurrentZoneInfo('mode') === 'country' && !empty($docLocale)) {
            $docCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        if (strpos($docLocale, '_') !== false) {
            $parts = explode('_', $docLocale);
            if (isset($parts[1]) && !empty($parts[1])) {
                $docCountryIso = $parts[1];
            }
        }

        $country = null;
        $language = null;

        $validLocales = $this->getCurrentZoneLocaleAdapter()->getActiveLocales();

        //domain has language, it's the root.
        if (!empty($docLocale)) {
            $isRootDomain = true;
            if (array_search($docLocale, array_column($validLocales, 'locale')) === false) {
                return false;
            }
        } else {
            $children = $domainDoc->getChildren(true);

            /** @var Document $child */
            foreach ($children as $child) {
                if (!in_array($child->getType(), ['page', 'hardlink', 'link'])) {
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
                        if (in_array($linkChild->getPath(), $loopDetector)) {
                            $validPath = false;

                            break;
                        }

                        if ($linkChild->getLinktype() !== 'internal') {
                            $validPath = false;

                            break;
                        } elseif ($linkChild->getInternalType() !== 'document') {
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

                if ($this->getCurrentZoneInfo('mode') === 'country') {
                    $childCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
                }

                if (strpos($childDocLocale, '_') !== false) {
                    $parts = explode('_', $childDocLocale);
                    if (isset($parts[1]) && !empty($parts[1])) {
                        $childCountryIso = $parts[1];
                    }
                }

                if (empty($childDocLocale) || array_search($childDocLocale, array_column($validLocales, 'locale')) === false) {
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

                $subPages[] = [
                    'id'               => $child->getId(),
                    'host'             => $domain,
                    'realHost'         => $domainHost,
                    'locale'           => $childDocLocale,
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
        if (!empty($docLocale)) {
            $realLang = explode('_', $docLocale);
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
            'locale'           => $docLocale,
            'countryIso'       => $docCountryIso,
            'languageIso'      => $docRealLanguageIso,
            'hrefLang'         => $hrefLang,
            'localeUrlMapping' => (!empty($docLocale)) ? $docLocale : null,
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
     * @param string $domain
     *
     * @return string
     */
    private function getDomainUrl($domain)
    {
        $scheme = \Pimcore\Tool::getRequestScheme();
        $domainHost = $this->getDomainHost($domain, false);
        $domainPort = $this->getDomainPort($domain);
        $domainUrl = $domainHost;

        if (strpos($domainUrl, 'http:') === false) {
            $domainUrl = $scheme . '://' . $domainUrl;
        }

        if (!empty($domainPort)) {
            $domainUrl = $domainUrl . ':' . $domainPort;
        }

        return rtrim($domainUrl, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $domain
     * @param bool   $stripWWW
     *
     * @return string
     */
    private function getDomainHost($domain, $stripWWW = true)
    {
        $urlInfo = parse_url($domain);
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : $urlInfo['path'];
        $host = $stripWWW ? preg_replace('/^www./', '', $host) : $host;

        return $host;
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    private function getDomainPort($domain)
    {
        $port = '';
        $urlInfo = parse_url($domain);
        if (isset($urlInfo['port']) && $urlInfo['port'] !== 80) {
            $port = $urlInfo['port'];
        }

        return $port;
    }

    /**
     * @param array $zoneDomains
     *
     * @return array
     */
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
     * create config array for adapter classes.
     *
     * @param array $config
     *
     * @return array
     */
    private function setZoneConfiguration($config)
    {
        $blackList = ['zones', 'mode', 'locale_adapter'];
        $validConfig = array_diff_key($config, array_flip($blackList));

        return $validConfig;
    }
}
