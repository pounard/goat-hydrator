<?php

namespace Goat\Hydrator\Bridge\Symfony;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
final class GoatHydratorConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('goat_hydrator');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('blacklist')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('target_dir')
                    ->info("Generated hydrator classes directory, defaults to current environment cache directory")
                    ->defaultValue('src/')
                ->end()
                ->scalarNode('target_namespace')
                    ->info("Generated hydrator classes namespace, default is a generated one, outside of your application")
                    ->defaultValue('App\\Hydrator')
                ->end()
                ->scalarNode('target_namespace_prefix')
                    ->info("Generated hydrator namespace prefix, it probaly should match your application PSR-4 autoload prefix")
                    ->defaultValue('App\\')
                ->end()
                ->enumNode('naming_strategy')
                    ->info("Generated hydrator classes naming strategy")
                    ->values(['hash', 'class'])
                    ->defaultValue('class')
                ->end()
                ->arrayNode('classes')
                    ->normalizeKeys(true)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('properties')
                                ->normalizeKeys(true)
                                ->prototype('scalar')->end()
                            ->end()
                            ->enumNode('constructor')
                                ->values(['normal', 'late', 'none'])
                                ->defaultValue('late')
                            ->end()
                         ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
