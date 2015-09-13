<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SQSTest extends WebTestCase
{
    static protected $queueCounter = 1;
    static protected $currentQueueName = '';

    protected function setUp()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
    }

    protected function tearDown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
            static::$kernel = null;
        }
    }

    /**
     * In case some test forgets to delete down a queue it created
     */
    public static function tearDownAfterClass()
    {
        if (self::$currentQueueName != '') {
            static::$kernel = static::createKernel(array());
            static::$kernel->boot();
            self::removeQueue();
            static::$kernel->shutdown();
            static::$kernel = null;
        }
    }

    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function getQueueName()
    {
        return self::$currentQueueName;
    }

    protected function getDriver()
    {
        return $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('sqs');
    }

    protected function getQueueManager()
    {
        return $this->getDriver()->getQueueManager(self::$currentQueueName);
    }

    protected function getConsumer($msgConsumer = null)
    {
        if (is_string($msgConsumer)) {
            $msgConsumer = $this->getContainer()->get($msgConsumer);
        }
        return $this->getDriver()->getConsumer(self::$currentQueueName, $msgConsumer);
    }

    protected function getMsgProducer($msgProducerServiceId)
    {
        return $this->getContainer()->get($msgProducerServiceId)
            ->setDriver($this->getDriver())
            ->setQueueName(self::$currentQueueName)
        ;
    }

    /**
     * Annoyingly static, as it is called by tearDownAfterClass
     */
    protected static function removeQueue()
    {
        $result = null;
        if (self::$currentQueueName != '') {
            $result = static::$kernel->
                getContainer()->
                get('kaliop_queueing.drivermanager')->
                getDriver('sqs')->
                getQueueManager(self::$currentQueueName)->
                executeAction('delete');
            self::$currentQueueName = null;
        }
        return $result;
    }

    protected function createQueue($removePrevious = true)
    {
        if ($removePrevious) {
            self::removeQueue();
        }

        // weird 2 lines in a row, but not a bug
        self::$currentQueueName = $this->getNewQueueName();
        self::$currentQueueName = $this->getQueueManager()->executeAction('create');

        // give SQS a little time for the queue to propagate properly (better would be possibly to execute a 'list' call)
        sleep(5);

        return self::$currentQueueName;
    }

    protected function getNewQueueName()
    {
        $buildId = 'travis_test_' . getenv('TRAVIS_JOB_NUMBER');
        if ($buildId == 'travis_test_') {
            $buildId = 'test_' . gethostname() . '_' . getmypid();
        }

        $buildId .= '_' . self::$queueCounter;
        self::$queueCounter++;
        return $buildId;
    }

}
