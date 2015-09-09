<?php

class ServicesTest extends SQSTest
{
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