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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('goat_hydrator');

        $rootNode
            ->children()
                ->arrayNode('blacklist')
                    ->prototype('scalar')->end()
                ->end()
                /*
                ->arrayNode('custom')
                    ->normalizeKeys(true)
                    ->prototype('')
                    ->end()
                ->end()
                 */
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
