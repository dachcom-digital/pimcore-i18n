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
                ->booleanNode('enable_context_switch_detector')->defaultValue(false)->end()
                ->scalarNode('request_scheme')->isRequired()->end()
                ->integerNode('request_port')->isRequired()->end()
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
                                ->prototype('variable')
                                    ->validate()
                                        ->ifTrue(function ($domain) {

                                            if (is_string($domain)) {
                                                return false;
                                            }

                                            if (!is_array($domain)) {
                                                return true;
                                            }

                                            if (!isset($domain[0]) || !is_string($domain[0])) {
                                                return true;
                                            }

                                            if (!isset($domain[1]) || !is_string($domain[1])) {
                                                return true;
                                            }

                                            if (!isset($domain[2]) || !is_int($domain[2])) {
                                                return true;
                                            }

                                            return false;
                                        })
                                        ->thenInvalid('Error in your domain setup %s. Use a string for domain name or an array in format [(string) domain.com, (string) https, (int) 99]')
                                    ->end()
                                ->end()
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
