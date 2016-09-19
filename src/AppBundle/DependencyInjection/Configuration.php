<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode('mediaconch')
                    ->children()
                        ->scalarNode('address')->end()
                        ->integerNode('port')->end()
                        ->scalarNode('api_version')->end()
                        ->arrayNode('absolute_url_for_mail')
                            ->info('Force url info for absolute url in email')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('host')
                                    ->defaultValue(null)
                                ->end()
                                ->scalarNode('scheme')
                                    ->defaultValue(null)
                                ->end()
                                ->scalarNode('baseUrl')
                                    ->defaultValue(null)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // mediaconch
            ->end()
        ;

        return $treeBuilder;
    }
}
