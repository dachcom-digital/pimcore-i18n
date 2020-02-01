<?php

namespace I18nBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use I18nBundle\Configuration\Configuration as BundleConfiguration;

class I18nExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator([__DIR__ . '/../Resources/config']));
        $loader->load('services.yml');
        $loader->load('profiler.yml');

        $configManagerDefinition = $container->getDefinition(BundleConfiguration::class);
        $configManagerDefinition->addMethodCall('setConfig', [$config]);

        $container->setParameter('i18n.registry_availability', $config['registry']);

        // set geo db path (including legacy path)
        if ($container->hasParameter('pimcore.geoip.db_file') && !is_null($container->getParameter('pimcore.geoip.db_file'))) {
            $geoIpDbFile = $container->getParameter('pimcore.geoip.db_file');
        } else {
            $geoIpDbFile = realpath(PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb');
        }

        $container->setParameter('i18n.geo_ip.db_file', is_string($geoIpDbFile) ? $geoIpDbFile : '');
    }
}
