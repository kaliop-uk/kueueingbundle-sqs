<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * Logic heavily inspired by the way that Oldsound/RabbitMqBundle does things
 */
class KaliopQueueingPluginsSQSExtension extends Extension
{
    protected $config = array();
    protected $container;
    protected $queueManagerService = 'kaliop_queueing.sqs.queue_manager';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $this->loadConnections();
        $this->loadQueues();
    }

    protected function loadConnections()
    {
        // this is not so much a loading as a 'store definition for later access', really
        $definition = $this->container->findDefinition('kaliop_queueing.driver.sqs');
        foreach ($this->config['connections'] as $key => $def) {
            $definition->addMethodCall('registerConnection', array($key, $def));
        }
    }

    protected function loadQueues()
    {
        $qmDefinition = null;
        if ($this->container->hasDefinition($this->queueManagerService)) {
            $qmDefinition = $this->container->findDefinition($this->queueManagerService);
        }

        foreach ($this->config['queues'] as $key => $consumer) {
            if (!isset($this->config['connections'][$consumer['connection']])) {
                throw new \RuntimeException("SQS queue '$key' can not use connection '{$consumer['connection']}' because it is not defined in the connections section");
            }

            $pDefinition = new Definition('%kaliop_queueing.sqs.producer.class%', array($this->config['connections'][$consumer['connection']]));
            $pDefinition
                ->addMethodCall('setQueueUrl', array($consumer['queue_options']['name']))
            ;
            if ($consumer['queue_options']['message_group_id'] != null) {
                $pDefinition->addMethodCall('setMessageGroupId', array($consumer['queue_options']['message_group_id']));
            }
            $name = sprintf('kaliop_queueing.sqs.%s_producer', $key);
            $this->container->setDefinition($name, $pDefinition);

            $cDefinition = new Definition('%kaliop_queueing.sqs.consumer.class%', array($this->config['connections'][$consumer['connection']]));
            $cDefinition
                ->addMethodCall('setQueueUrl', array($consumer['queue_options']['name']))
                ->addMethodCall('setCallback', array(new Reference($consumer['callback'])));
            ;
            if (count($consumer['queue_options']['routing_keys'])) {
                $cDefinition->addMethodCall('setRoutingKey', array(reset($consumer['queue_options']['routing_keys'])));
            }
            if ($consumer['queue_options']['max_messages_per_request'] > 1) {
                $cDefinition->addMethodCall('setRequestBatchSize', array($consumer['queue_options']['max_messages_per_request']));
            }
            if ($consumer['queue_options']['request_timeout'] > 0) {
                $cDefinition->addMethodCall('setRequestTimeout', array($consumer['queue_options']['request_timeout']));
            }
            if ($consumer['queue_options']['gc_probability'] != 1) {
                $cDefinition->addMethodCall('setGCProbability', array($consumer['queue_options']['gc_probability']));
            }
            if ($consumer['queue_options']['polling_interval'] != 200000) {
                $cDefinition->addMethodCall('setPollingInterval', array($consumer['queue_options']['polling_interval']));
            }
            $name = sprintf('kaliop_queueing.sqs.%s_consumer', $key);
            $this->container->setDefinition($name, $cDefinition);

            //if (!$consumer['auto_setup_fabric']) {
            //    $definition->addMethodCall('disableAutoSetupFabric');
            //}

            if ($qmDefinition) {
                $qmDefinition->addMethodCall('registerQueue', array($key));
            }
        }
    }
}
