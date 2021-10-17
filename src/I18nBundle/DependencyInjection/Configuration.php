<?php

namespace I18nBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('i18n');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('mode')
                    ->values(['country', 'language'])
                    ->defaultValue('language')
                    ->info('')
                ->end()
                ->enumNode('redirect_status_code')->defaultValue(302)->values([301,302])->end()
                ->arrayNode('registry')
                    ->addDefaultsIfNotSet()
                    ->info('')
                    ->children()
                        ->arrayNode('redirector')
                            ->useAttributeAsKey('type')
                            ->normalizeKeys(false)
                            ->arrayPrototype()
                                ->children()
                                    ->booleanNode('enabled')->defaultTrue()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('locale_adapter')
                    ->defaultValue('system')
                    ->info('')
                    ->validate()
                        ->ifTrue(function ($v) {
                            return empty($v);
                        })
                        ->thenInvalid('you must define a locale adapter')
                    ->end()
                ->end()
                ->scalarNode('default_locale')
                    ->defaultNull()
                    ->info('')
                ->end()
                ->arrayNode('translations')
                    ->defaultValue([])
                    ->info('')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('key')->end()
                            ->variableNode('values')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('zones')
                    ->defaultValue([])
                    ->useAttributeAsKey('identifier')
                    ->prototype('array')
                        ->children()
                            ->integerNode('id')->isRequired()->end()
                            ->scalarNode('name')->defaultValue(null)->end()
                            ->arrayNode('domains')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('config')
                                ->children()
                                    ->enumNode('mode')
                                        ->values(['country', 'language'])
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('locale_adapter')
                                        ->isRequired()
                                        ->info('')
                                        ->validate()
                                            ->ifTrue(function ($v) {
                                                return empty($v);
                                            })
                                            ->thenInvalid('you must define a locale adapter')
                                        ->end()
                                    ->end()
                                    ->scalarNode('default_locale')
                                        ->defaultValue(null)
                                        ->info('')
                                    ->end()
                                    ->arrayNode('translations')
                                        ->isRequired()
                                        ->info('')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('key')->end()
                                                ->variableNode('values')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return $v['enabled'] === false;
                            })->thenUnset()
                        ->end()
                        ->canBeUnset()
                        ->canBeDisabled()
                        ->treatNullLike(['enabled' => false])
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
