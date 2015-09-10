<?php

require_once(__DIR__.'/SQSTest.php');

class MessagesTest extends SQSTest
{
    public function testSendAndReceiveMessage()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueuname($this->queueName)->publish('{"hello":"world"}');

        $this->assertContains('world', $driver->getConsumer($this->queueName)->consume(1));
    }

    public function testSendAndReceiveMessageWithRouting()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueuname($this->queueName);
        $msgProducer->publish('{"hello":"world"}', null, 'hello.world');

        $consumer = $driver->getConsumer($this->queueName);
        $this->assertContains('world', $consumer->setRoutingkey('hello.world')->consume(1));

    }

    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueuname($this->queueName);
        $msgProducer->publish('{"hello":"w1"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w2"}', null, 'hello.world');

        $consumer = $driver->getConsumer($this->queueName);
        $this->assertContains('w1', $consumer->setRoutingkey('hello.*')->consume(1));
        $this->assertContains('w2', $consumer->setRoutingkey('*.world')->consume(1));
    }

    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueuname($this->queueName);
        $msgProducer->publish('{"hello":"w1"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w2"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w3"}', null, 'hello.world');

        $consumer = $driver->getConsumer($this->queueName);
        $this->assertContains('w1', $consumer->setRoutingkey('hello.#')->consume(1));
        $this->assertContains('w2', $consumer->setRoutingkey('#.world')->consume(1));
        $this->assertContains('w2', $consumer->setRoutingkey('#')->consume(1));
    }
}