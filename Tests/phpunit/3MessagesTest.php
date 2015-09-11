<?php

require_once(__DIR__.'/SQSTest.php');

/**
 * @todo since queue purge has to wait 60 secs, it might be faster to create and tear down a new queue for each test...
 */
class MessagesTest extends SQSTest
{
    // maximum number of seconds to wait for the queue when consuming
    protected $timeout = 10;

    public function testSendAndReceiveMessage()
    {
        $this->purgeQueue();

        $msgProducer = $this->getMsgProducer('kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"world"}');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $this->getConsumer()->consume(1);
        $this->assertContains('world', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRouting()
    {
        $this->purgeQueue();

        $msgProducer = $this->getMsgProducer('kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer();

        $consumer->setRoutingkey('hello.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('bonjour.monde')->consume(1, $this->timeout);
        $this->assertContains('fre', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $this->purgeQueue();

        $msgProducer = $this->getMsgProducer('kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer();

        $consumer->setRoutingkey('hello.*')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('*.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $this->purgeQueue();

        $msgProducer = $this->getMsgProducer('kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer();

        $consumer->setRoutingkey('hello.#')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('#.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        // this could give us back either message, as order of delivery is not guaranteed
        $consumer->setRoutingkey('#')->consume(1, $this->timeout);
        $this->assertThat(
            $accumulator->getConsumptionResult(),
            $this->logicalOr(
                $this->contains('eng'),
                $this->contains('fre')
            )
        );
    }
}
