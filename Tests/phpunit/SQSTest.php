<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SQSTest extends WebTestCase
{
    protected $queueName = 'https://sqs.us-east-1.amazonaws.com/139046234059/travisTests';

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

    protected function getDriver()
    {
        return static::$kernel->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('sqs');
    }
}
