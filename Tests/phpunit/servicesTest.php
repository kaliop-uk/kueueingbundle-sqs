<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class servicesTest extends WebTestCase
{
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
}