<?php

require_once(__DIR__.'/SQSTest.php');

class QueueManagementTests extends SQSTest
{
    public function testListQueues()
    {
        $queueManager = $this->getDriver()->getQueueManager(null);
        $this->assertContains($this->getQueueName(), $queueManager->executeAction('list'));
    }

    public function testQueueInfo()
    {
        $queueManager = $this->getQueueManager();
        $this->assertArrayHasKey('QueueArn', $queueManager->executeAction('info'));
    }

    public function testQueuePurge()
    {
        $queueManager = $this->getQueueManager();
        // in case this test is run just after another one
        sleep(60);
        $this->assertContains($this->getQueueName(), $queueManager->executeAction('purge'));
    }
}