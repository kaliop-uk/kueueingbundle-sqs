<?php

require_once(__DIR__.'/SQSTest.php');

class MessagesTest extends SQSTest
{
    // maximum number of seconds to wait for the queue when consuming
    protected $timeout = 10;

    public function testSendAndReceiveMessage()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueueName($this->queueName)->publish('{"hello":"world"}');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $driver->getConsumer($this->queueName)->consume(1);
        $this->assertContains('world', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRouting()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueueName($this->queueName);
        $msgProducer->publish('{"hello":"route"}', null, 'hello.world');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $driver->getConsumer($this->queueName);
        $consumer->setRoutingkey('hello.world')->consume(1, $this->timeout);
        $this->assertContains('route', $accumulator->getConsumptionResult());
    }

    /// @todo we should make sure the queue is empty before running this test
    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueueName($this->queueName);
        $msgProducer->publish('{"hello":"w1"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w2"}', null, 'hello.world');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $driver->getConsumer($this->queueName);
        $consumer->setRoutingkey('hello.*')->consume(1, $this->timeout);
        $this->assertContains('w1', $accumulator->getConsumptionResult());
        $consumer->setRoutingkey('*.world')->consume(1, $this->timeout);
        $this->assertContains('w2', $accumulator->getConsumptionResult());
    }

    /// @todo we should make sure the queue is empty before running this test
    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $driver = $this->getDriver();

        $msgProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $msgProducer->setDriver($driver)->setQueueName($this->queueName);
        $msgProducer->publish('{"hello":"w3"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w4"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"w5"}', null, 'hello.world');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $driver->getConsumer($this->queueName);
        $consumer->setRoutingkey('hello.#')->consume(1, $this->timeout);
        $this->assertContains('w3', $accumulator->getConsumptionResult());
        $consumer->setRoutingkey('#.world')->consume(1, $this->timeout);
        $this->assertContains('w4', $accumulator->getConsumptionResult());
        $consumer->setRoutingkey('#')->consume(1, $this->timeout);
        $this->assertContains('w5', $accumulator->getConsumptionResult());
    }
}
