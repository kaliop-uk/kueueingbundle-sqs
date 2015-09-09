<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class servicesTest extends WebTestCase
{
    protected $queueName = 'https://sqs.us-east-1.amazonaws.com/139046234059';

    protected function getContainer()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
        return static::$kernel->getContainer();
    }

    /**
     * Minimalistic test: check that all known services can be loaded
     */
    public function testKnownServices()
    {
        $container = $this->getContainer();
        $service = $container->get('kaliop_queueing.driver.sqs');
        $service = $container->get('kaliop_queueing.sqs.queue_manager');
        $service = $container->get('kaliop_queueing.sqs.producer');
        $service = $container->get('kaliop_queueing.sqs.consumer');
    }

    public function testSendAndReceiveMessage()
    {
        $container = $this->getContainer();
        $driver = $container->get('kaliop_queueing.drivermanager')->getDriver('sqs');

        $service = $container->get('kaliop_queueing.message_producer.generic_message');
        $service->setDriver($driver)->setQueuname($this->queueName)->publish('{"hello":"world"}');

        $driver->getConsumer($this->queueName)->consume(1);
    }
}