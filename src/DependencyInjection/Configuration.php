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
                ->enumNode('redirect_status_code')->defaultValue(302)->values([301, 302])->end()
                ->booleanNode('enable_context_switch_detector')->defaultValue(false)->end()
                ->scalarNode('request_scheme')->defaultValue('https')->end()
                ->integerNode('request_port')->defaultValue(443)->end()
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
                                    ->variableNode('config')->end()
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
