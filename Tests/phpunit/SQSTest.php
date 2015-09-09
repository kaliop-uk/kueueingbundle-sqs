<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class SQSTests extends WebTestCase
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
}
