<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * @todo inject Debug flag in both consumers and producers
 */
class Driver extends ContainerAware implements DriverInterface
{
    protected $debug;

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
        $consumer = $this->container->get("kaliop_queueing.sqs.{$queueName}_consumer");
        /*$consumer = $this->container->get('kaliop_queueing.sqs.consumer');
        $consumer->setQueueName($queueName);
        if ($callback == null) {
            $callback = $this->getQueueCallbackFromConfig($queueName);
        }
        $consumer->setCallback($callback);*/
        return $consumer;
    }

    protected function getQueueCallbackFromConfig($queueName)
    {
        $callbacks = $this->container->getParameter('kaliop_queueing_sqs.default.consumers');
        if (!isset($callbacks[$queueName]) || !isset($callbacks[$queueName]['callback'])) {
            throw new \UnexpectedValueException("No callback has been defined for queue '$queueName', please check config parameter 'kaliop_queueing_sqs.default.consumers'");
        }
        return $this->container->get($callbacks[$queueName]['callback']);
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
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    public function getProducer($queueName)
    {

        $producer = $this->container->get("kaliop_queueing.sqs.{$queueName}_producer");
        //$producer->setQueueName($queueName);
        return $producer;
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
}
