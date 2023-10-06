<?php

namespace I18nBundle\Builder;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Model\Zone;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Registry\LocaleProviderRegistry;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\Site;

class ZoneBuilder
{
    protected Configuration $configuration;
    protected LocaleProviderRegistry $localeProviderRegistry;

    public function __construct(
        Configuration $configuration,
        LocaleProviderRegistry $localeProviderRegistry
    ) {
        $this->configuration = $configuration;
        $this->localeProviderRegistry = $localeProviderRegistry;
    }

    public function buildZone(RouteItemInterface $routeItem): ZoneInterface
    {
        $site = $routeItem->getRouteContextBag()->get('site');

        $zones = $this->configuration->getConfig('zones');

        // no zones defined
        if (empty($zones)) {
            return $this->createZone($this->configuration->getConfigNode());
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

        return $this->createZone($zoneConfig['config'], $zoneConfig['id'], $zoneConfig['name'], $zoneConfig['domains']);
    }

    protected function createZone(
        array $zoneDefinition,
        ?int $currentZoneId = null,
        ?string $currentZoneName = null,
        array $currentZoneDomains = []
    ): ZoneInterface {

        if (!empty($zoneDefinition['locale_adapter']) && !$this->localeProviderRegistry->has($zoneDefinition['locale_adapter'])) {
            throw new \Exception(sprintf(
                'locale provider "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                $zoneDefinition['locale_adapter'],
                'i18n.adapter.locale',
                $zoneDefinition['locale']
            ));
        }

        $translations = array_merge($this->configuration->getConfig('translations'), $zoneDefinition['translations']);

        return new Zone(
            $currentZoneId,
            $currentZoneName,
            $zoneDefinition['default_locale'],
            $zoneDefinition['locale_adapter'],
            $translations,
            $currentZoneDomains
        );
    }
}
