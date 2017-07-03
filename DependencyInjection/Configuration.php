<?php

namespace I18nBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('i18n');

        $rootNode
            ->children()
                ->scalarNode('mode')
                    ->isRequired()
                    ->info('')
                ->end()
                ->scalarNode('country_adapter')
                    ->isRequired()
                    ->info('')
                ->end()
                ->arrayNode('translations')
                    ->isRequired()
                    ->info('')
                ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}