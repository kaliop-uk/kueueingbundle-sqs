<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();

        $rootNode = $tree->root('kaliop_queueing_sqs');

        $this->addConnections($rootNode);
        $this->addQueues($rootNode);

        return $tree;
    }

    protected function addConnections(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('credentials')
                                //->useAttributeAsKey('key')
                                //->canBeUnset()
                                //->prototype('variable')->end()
                            ->end()
                            ->scalarNode('region')->isRequired()->end()
                            ->scalarNode('version')->defaultValue('latest')->end()
                            ->booleanNode('debug')->defaultFalse()->end()
                            ->arrayNode('https')
                                //->useAttributeAsKey('key')
                                //->canBeUnset()
                                //->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addQueues(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('consumer')
            ->children()
                ->arrayNode('queues')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end() // Q: could it be made optional?
                            //->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function getQueueConfiguration()
    {
        $node = new ArrayNodeDefinition('queue_options');

        $this->addQueueNodeConfiguration($node);

        return $node;
    }

    /**
     * @todo we use an array for routing keys, as RabbitMQ config does, but we probably only support one
     * @param ArrayNodeDefinition $node
     */
    protected function addQueueNodeConfiguration(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('name')->end()
                ->arrayNode('routing_keys')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
            ->end()
        ;
    }
}