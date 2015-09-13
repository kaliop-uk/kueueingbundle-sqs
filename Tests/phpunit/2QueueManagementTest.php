<?php

require_once(__DIR__.'/SQSTest.php');

class QueueManagementTests extends SQSTest
{
    public function testCreateQueue()
    {
        $this->assertInternalType('string', $this->CreateQueue());
    }

    public function testListQueues()
    {
        $queueManager = $this->getDriver()->getQueueManager(null);
        $this->assertArrayHasKey($this->getQueueName(), $queueManager->executeAction('list'));
    }

    public function testQueueInfo()
    {
        $queueManager = $this->getQueueManager();
        $this->assertArrayHasKey('QueueArn', $queueManager->executeAction('info'));
    }

    public function testQueuePurge()
    {
        $queueManager = $this->getQueueManager();
        $this->assertContains($this->getQueueName(), $queueManager->executeAction('purge'));
    }

    public function testQueueDelete()
    {
        $this->assertInternalType('array', self::removeQueue());
    }
}