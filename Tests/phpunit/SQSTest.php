<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SQSTest extends WebTestCase
{
    private $queueName = 'https://sqs.us-east-1.amazonaws.com/139046234059/travisTests';

    protected function setUp()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
    }

    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function getQueueName()
    {
        return $this->queueName;
    }

    protected function getDriver()
    {
        return $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('sqs');
    }

    protected function getQueueManager()
    {
        return $this->getDriver()->getQueueManager($this->getQueueName());
    }

    protected function getConsumer()
    {
        return $this->getDriver()->getConsumer($this->getQueueName());
    }

    protected function getMsgProducer($msgProducerServiceId)
    {
        return $this->getContainer()->get($msgProducerServiceId)
            ->setDriver($this->getDriver())
            ->setQueueName($this->getQueueName())
        ;
    }

    /**
     * SQS allows only 1 purge command every 60 secs...
     */
    protected function purgeQueue()
    {
        sleep(60);
        $this->getQueueManager()->executeAction('purge');
    }
}
