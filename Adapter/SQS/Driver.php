<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Driver extends ContainerAware implements DriverInterface
{
    protected $debug;

    public function getConsumer($queueName)
    {
        $consumer = $this->container->get('kaliop_queueing.sqs.consumer');
        $callback = $this->getQueueCallback($queueName);
        $consumer->setQueueName($queueName);
        $consumer->setCallback($callback);
        return $consumer;
    }

    protected function getQueueCallback($queueName)
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
        $producer = $this->container->get('kaliop_queueing.sqs.producer');
        $producer->setQueueName($queueName);
        $producer->setDebug($this->debug);
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
