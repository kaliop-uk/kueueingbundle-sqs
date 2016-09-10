<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo inject Debug flag in both consumers and producers
 */
class Driver implements DriverInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $debug;
    protected $connections;

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    public function getProducer($queueName)
    {
        return $this->container->get("kaliop_queueing.sqs.{$queueName}_producer")->setDebug($this->debug);
    }

    /**
     * This method is more flexible than what is declared in the interface, as it allows direct injection of a callback
     * by the caller instead of relying solely on service configuration.
     * It helps when queues are created dynamically.
     *
     * @param string $queueName
     * @param MessageConsumerInterface|null $callback when null, the appropriate MessageConsumer for the queue is looked
     *                                                up in service configuration
     * @return object
     */
    public function getConsumer($queueName, MessageConsumerInterface $callback = null)
    {
        return $this->container->get("kaliop_queueing.sqs.{$queueName}_consumer")->setDebug($this->debug)->setQueueName($queueName);
    }

    public function acceptMessage($message)
    {
        return $message instanceof \Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS\Message;
    }

    /**
     * Unlike the RabbitMQ driver, we do not have to deal with a native message type from the underlying library.
     * So we just let the Producer create messages of the good type, and decoding them becomes a no-op
     *
     * @param \Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS\Message $message
     * @return \Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS\Message
     */
    public function decodeMessage($message)
    {
        return $message;
    }

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\QueueManagerInterface
     */
    public function getQueueManager($queueName)
    {
        $mgr = $this->container->get('kaliop_queueing.sqs.queue_manager');
        $mgr->setQueueName($queueName);
        return $mgr;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param string $connectionId
     * @param array $params
     */
    public function registerConnection($connectionId, array $params)
    {
        $this->connections[$connectionId] = $params;
    }

    protected function getConnectionConfig($connectionId)
    {
        if (!isset($this->connections[$connectionId])) {
            throw new \RuntimeException("Connection '$connectionId' is not registered with SQS driver");
        }

        return $this->connections[$connectionId];
    }

    /**
     * Dynamically creates a producer, with no need for configuration except for the connection configuration
     *
     * @param string $queueName
     * @param string $queueUrl
     * @param string $connectionId
     * @return mixed
     */
    public function createProducer($queueName, $queueUrl, $connectionId)
    {
        $class = $this->container->getParameter('kaliop_queueing.sqs.producer.class');
        $producer = new $class($this->getConnectionConfig($connectionId));
        $producer->setQueueUrl($queueUrl);
        $this->container->set("kaliop_queueing.sqs.{$queueName}_producer", $producer);
        return $producer;
    }

    /**
     * Dynamically creates a consumer, with no need for configuration except for the connection configuration
     *
     * @param string $queueName
     * @param string $queueUrl
     * @param string $connectionId Id of a connection as defined in configuration
     * @param MessageConsumerInterface $callback
     * @param string $routingKey
     * @param string $scope
     * @return Consumer
     */
    public function createConsumer($queueName, $queueUrl, $connectionId, $callback=null, $routingKey=null, $scope=ContainerInterface::SCOPE_CONTAINER)
    {
        $class = $this->container->getParameter('kaliop_queueing.sqs.consumer.class');
        $consumer = new $class($this->getConnectionConfig($connectionId));
        $consumer->setQueueUrl($queueUrl)->setRoutingKey($routingKey)->setQueueName($queueName);
        if ($callback != null) {
            $consumer->setCallBack($callback);
        }
        $this->container->set("kaliop_queueing.sqs.{$queueName}_consumer", $consumer, $scope);
        return $consumer;
    }
}
