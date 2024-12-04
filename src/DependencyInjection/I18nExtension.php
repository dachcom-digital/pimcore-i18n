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

namespace I18nBundle\DependencyInjection;

use I18nBundle\Configuration\Configuration as BundleConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class I18nExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());

        // first, we need to resolve parameters
        // => allow placeholders in translation blocks for example
        $resolvingBag = $container->getParameterBag();
        $configs = $resolvingBag->resolveValue($configs);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $zoneTranslations = array_values(array_map(static function (array $zone) {
            return $zone['config']['translations'];
        }, $config['zones']));

        $translations = array_merge($config['translations'], ...$zoneTranslations);

        foreach ($translations as $translation) {
            $translationKey = sprintf('i18n.route.translations.%s', $translation['key']);
            $translationValue = implode('|', $translation['values']);

            if ($container->hasParameter($translationKey)) {
                continue;
            }

            $container->setParameter($translationKey, $translationValue);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator([__DIR__ . '/../../config']));
        $loader->load('services.yaml');
        $loader->load('profiler.yaml');

        $configManagerDefinition = $container->getDefinition(BundleConfiguration::class);
        $configManagerDefinition->addMethodCall('setConfig', [$config]);

        $container->setParameter('i18n.registry', $config['registry']);

        // set geo db path (including legacy path)
        /* @phpstan-ignore-next-line */
        if ($container->hasParameter('pimcore.geoip.db_file') && !empty($container->getParameter('pimcore.geoip.db_file'))) {
            $geoIpDbFile = $container->getParameter('pimcore.geoip.db_file');
        } else {
            $geoIpDbFile = realpath(PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb');
        }

        $container->setParameter('i18n.geo_ip.db_file', is_string($geoIpDbFile) ? $geoIpDbFile : '');
    }
}
