<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Country\AbstractCountry;
use I18nBundle\Adapter\Language\AbstractLanguage;
use I18nBundle\Adapter\Language\LanguageInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Adapter\Country\CountryInterface;
use I18nBundle\Registry\CountryRegistry;
use I18nBundle\Registry\LanguageRegistry;
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

            return;
        }

        //it's not a site request, zones are invalid. use the default settings.
        if (Site::isSiteRequest() === FALSE) {
            $this->currentZone = $this->mapData($this->configuration->getConfigNode());

            return;
        }

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

            return;
        }

        $this->isInZone = TRUE;

        $parsedZoneConfig = $this->mapData($zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name']);
        $parsedZoneConfig['valid_domains'] = $zoneConfig['domains'];

        $this->currentZone = $parsedZoneConfig;
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
     * @return CountryInterface
     * @throws \Exception
     */
    public function isInZone()
    {
        return $this->isInZone();
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
}