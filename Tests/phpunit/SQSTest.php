<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SQSTest extends WebTestCase
{
    static protected $queueCounter = 1;
    protected $createdQueues = array();

    protected function setUp()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();

        $this->createdQueues = array();
    }

    protected function tearDown()
    {
        if (count($this->createdQueues)) {
            foreach(array_keys($this->createdQueues) as $queueName) {
                $this->removeQueue($queueName);
            }
        }

        if (null !== static::$kernel) {
            static::$kernel->shutdown();
            static::$kernel = null;
        }
    }

    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function getDriver()
    {
        return $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('sqs');
    }

    protected function getQueueManager($queueName)
    {
        return $this->getDriver()->getQueueManager($queueName);
    }

    protected function getConsumer($queueName, $msgConsumer = null)
    {
        $consumer = $this->getDriver()->getConsumer($queueName);
        if (is_string($msgConsumer)) {
            $consumer->setCallback($this->getContainer()->get($msgConsumer));
        }
        return $consumer;
    }

    protected function getMsgProducer($queueName, $msgProducerServiceId)
    {
        return $this->getContainer()->get($msgProducerServiceId)
            ->setDriver($this->getDriver())
            ->setQueueName($queueName)
        ;
    }

    /**
     * @param string $queueName
     * @return null|array
     */
    protected function removeQueue($queueName)
    {
        unset($this->createdQueues[$queueName]);
        return static::$kernel->
            getContainer()->
            get('kaliop_queueing.drivermanager')->
            getDriver('sqs')->
            getQueueManager($queueName)->
            executeAction('delete');
    }

    protected function createQueue($queueConfig = array())
    {
        $queueName = $this->getNewQueueName(isset($queueConfig['FifoQueue']) && $queueConfig['FifoQueue']);
        $driver = $this->getDriver();

        // tricky bit: create the queue, as well as a producer and consumer. But to create the queue, we need a producer first!
        $driver->createProducer($queueName, null, 'default');
        $queueUrl = $driver->getQueueManager($queueName)->executeAction('create', $queueConfig);
        $driver->getProducer($queueName)->setQueueUrl($queueUrl);
        if (isset($queueConfig['FifoQueue']) && $queueConfig['FifoQueue'] == 'true') {
            $driver->getProducer($queueName)->setMessageGroupId('hello');
            if (!isset($queueConfig['ContentBasedDeduplication']) || $queueConfig['ContentBasedDeduplication'] != 'true') {
                $driver->getProducer($queueName)->setMessageDeduplicationIdCalculator(
                    $this->getContainer()->get('kaliop_queueing.message_producer.deduplication_id_calculator.sequence')
                );
            }
        }
        $driver->createConsumer($queueName, $queueUrl, 'default');

        // save the id of the created queue
        $this->createdQueues[$queueName] = time();

        // give SQS a little time for the queue to propagate properly
        $queueManager = $driver->getQueueManager($queueName);
        for ($i = 0; $i < 15; $i++) {
            try {
                $info = $queueManager->executeAction('info');
                break;
            } catch (\Exception $e) {
                // do nothing
                var_dump($e);
            }
            sleep(1);
        }

        return $queueName;
    }

    protected function getNewQueueName($isFifo = false)
    {
        $buildId = 'travis_test_' . getenv('TRAVIS_JOB_NUMBER');
        if ($buildId == 'travis_test_') {
            $buildId = 'test_' . gethostname() . '_' . getmypid();
        }

        $buildId .= '_' . self::$queueCounter;
        self::$queueCounter++;

        $qName = str_replace( '.', '_', $buildId );

        if ($isFifo) {
            $qName .= '.fifo';
        }

        return $qName;
    }
}
