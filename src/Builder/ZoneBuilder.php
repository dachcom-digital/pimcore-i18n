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

namespace I18nBundle\Builder;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\Zone;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Registry\LocaleProviderRegistry;
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
            $zoneConfig = $this->configuration->getConfigNode();
            return $this->createZone($zoneConfig, $site->getId(), 'dynamic_zone_'.$site->getId(), [$site->getMainDomain()]);
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
