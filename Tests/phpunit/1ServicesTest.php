<?php

require_once(__DIR__.'/SQSTest.php');

class ServicesTest extends SQSTest
{
    /**
     * Minimalistic test: check that all known services can be loaded
     */
    public function testKnownServices()
    {
        $container = $this->getContainer();
        $service = $container->get('test_alias.kaliop_queueing.driver.sqs');
        $service = $container->get('test_alias.kaliop_queueing.sqs.queue_manager');
    }
}
