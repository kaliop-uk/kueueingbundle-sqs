<?php

require_once(__DIR__.'/SQSTest.php');

class MessagesTest extends SQSTest
{
    public function testSendAndReceiveMessage()
    {
        $container = $this->getContainer();
        $driver = $container->get('kaliop_queueing.drivermanager')->getDriver('sqs');

        $service = $container->get('kaliop_queueing.message_producer.generic_message');
        $service->setDriver($driver)->setQueuname($this->queueName)->publish('{"hello":"world"}');

        $driver->getConsumer($this->queueName)->consume(1);
    }
}