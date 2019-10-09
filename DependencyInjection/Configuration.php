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

        $rootNode = $tree->root('kaliop_queueing_plugins_sqs');

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
                            ->variableNode('credentials')
                                //->children()
                                //->whatever...
                                //->end()
                            ->end()
                            ->scalarNode('region')->isRequired()->end()
                            ->scalarNode('version')->defaultValue('latest')->end()
                            ->booleanNode('debug')->defaultFalse()->end()
                            ->variableNode('http')
                                //->children()
                                    //->whatever...
                                //->end()
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
     * @todo we use an array for routing keys, as RabbitMQ config does, but we currently only support one
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
                ->integerNode('max_messages_per_request')->min(1)->defaultValue(1)->end()
                ->integerNode('request_timeout')->min(0)->defaultValue(0)->end()
                ->integerNode('polling_interval')->min(0)->defaultValue(200000)->end()
                ->integerNode('gc_probability')->min(0)->max(100)->defaultValue(1)->end()
                ->scalarNode('message_group_id')->defaultValue(null)->end()
                ->scalarNode('message_deduplication_id_calculator')->defaultValue(null)->end()
            ->end()
        ;
    }
}
