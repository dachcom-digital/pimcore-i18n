<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Country\AbstractCountry;
use I18nBundle\Adapter\Language\AbstractLanguage;
use I18nBundle\Adapter\Language\LanguageInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Adapter\Country\CountryInterface;
use I18nBundle\Registry\CountryRegistry;
use I18nBundle\Registry\LanguageRegistry;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

class ZoneManager
{
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
     * @var
     */
    protected $currentZone = NULL;

    /**
     * Stores the current Zone domains
     * @var
     */
    protected $currentZoneDomains = NULL;

    /**
     * @var bool
     */
    protected $isInZone = FALSE;

    /**
     * {@inheritdoc}
     */
    public function __construct(Configuration $configuration, LanguageRegistry $languageRegistry, CountryRegistry $countryRegistry)
    {
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
            throw new \Exception('current zone already defined');
        }

        $zones = $this->configuration->getConfig('zones');

        //no zones defined
        if (empty($zones)) {
            $this->currentZone = $this->mapData($this->configuration->getConfigNode());
        } else {
            //it's not a site request, zones are invalid. use the default settings.
            if (Site::isSiteRequest() === FALSE) {
                $this->currentZone = $this->mapData($this->configuration->getConfigNode());
            } else {

                $validZone = FALSE;
                $zoneConfig = [];
                $currentSite = Site::getCurrentSite();

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
     * @param null $slot
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentZoneInfo($slot = NULL)
    {
        if (empty($this->currentZone)) {
            throw new \Exception('current zone is not configured');
        } else if (!array_key_exists($slot, $this->currentZone)) {
            throw new \Exception(sprintf('current zone config slot "%s" is not defined', $slot));
        }

        return $this->currentZone[$slot];
    }

    public function getCurrentZoneDomains($flatten = FALSE)
    {
        if (empty($this->currentZoneDomains)) {
            throw new \Exception('current zone domains not configured');
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
            throw new \Exception('current zone is not configured');
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
            throw new \Exception('current zone is not configured');
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

        $this->currentZoneDomains = $zoneDomains;
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
                    'country adapter "%s" is not available. please use "%s" tag to register new adapter.',
                    $config['country_adapter'], 'i18n.adapter.country')
            );
        }

        /** @var AbstractLanguage $countryAdapter */
        $languageAdapter = $this->languageRegistry->has($config['language_adapter'])
            ? $this->languageRegistry->get($config['language_adapter'])
            : NULL;

        if (!is_null($languageAdapter) && !is_null($zoneId)) {
            $languageAdapter->setCurrentZoneId($zoneId);
        }

        /** @var AbstractCountry $countryAdapter */
        $countryAdapter = $this->countryRegistry->has($config['country_adapter'])
            ? $this->countryRegistry->get($config['country_adapter'])
            : NULL;

        if (!is_null($countryAdapter) && !is_null($zoneId)) {
            $countryAdapter->setCurrentZoneId($zoneId);
        }

        $mapData = $this->currentZone = [
            'zoneId'           => $zoneId,
            'zoneName'         => $zoneName,
            'mode'             => $config['mode'],
            'global_prefix'    => $config['global_prefix'],
            'translations'     => $config['translations'],
            'language_adapter' => $languageAdapter,
            'country_adapter'  => $countryAdapter
        ];

        return $mapData;
    }

    private function mapDomainData($domain, $rootId)
    {
        $scheme = \Pimcore\Tool::getRequestScheme();
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

                if (!in_array($child->getType(), ['page', 'hardlink']) || !$child->isPublished()) {
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

                //we can't use the fullPath since PathFinder will transform all "out-of-context" paths.
                $urlKey = $child->getKey();
                $prefix = $scheme . '://' . $domainHost;
                $url = $prefix . '/' . $urlKey;

                $realLang = explode('_', $childLanguageIso);
                $hrefLang = strtolower($realLang[0]);
                if (!empty($childCountryIso) && $childCountryIso !== 'GLOBAL') {
                    $hrefLang .= '-' . strtolower($childCountryIso);
                }

                $subPages[] = [
                    'id'          => $child->getId(),
                    'host'        => $domain,
                    'realHost'    => $domainHost,
                    'countryIso'  => $childCountryIso,
                    'languageIso' => $realLang[0],
                    'hrefLang'    => $hrefLang,
                    'key'         => $urlKey,
                    'url'         => $url,
                    'fullPath'    => $child->getRealFullPath(),
                    'type'        => $child->getType()
                ];
            }
        }

        $domainUrl = $domainHost;
        if (strpos($domainUrl, 'http:') === FALSE) {
            $domainUrl = $scheme . '://' . $domainUrl;
        }

        $hrefLang = '';
        if(!empty($docLanguageIso)) {
            $realLang = explode('_', $docLanguageIso);
            $docLanguageIso = $realLang[0];
            $hrefLang = strtolower($docLanguageIso);
            if (!empty($docCountryIso) && $docCountryIso !== 'GLOBAL') {
                $hrefLang .= '-' . strtolower($docCountryIso);
            }
        }

        $domainData = [
            'id'           => $rootId,
            'host'         => $domain,
            'realHost'     => $domainHost,
            'isRootDomain' => $isRootDomain,
            'countryIso'   => $docCountryIso,
            'languageIso'  => $docLanguageIso,
            'hrefLang'     => $hrefLang,
            'key'          => NULL,
            'url'          => $domainUrl,
            'fullPath'     => $domainDoc->getRealFullPath(),
            'type'         => $domainDoc->getType(),
            'subPages'     => $subPages
        ];

        return $domainData;
    }

    private function getDomainHost($domain)
    {
        $urlInfo = parse_url($domain);
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : $urlInfo['path'];
        $host = preg_replace('/^www./', '', $host);

        return $host;
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

            unset($domain['subPages']);

            if(empty($domain['countryIso']) && empty($domain['languageIso'])) {
                continue;
            }

            $elements[] = $domain;
        }

        return $elements;
    }

}