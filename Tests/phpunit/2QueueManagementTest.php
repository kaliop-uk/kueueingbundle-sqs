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
        $queueName = $this->CreateQueue();
        $queueManager = $this->getDriver()->getQueueManager($queueName);
        $producer = $this->getDriver()->getProducer($queueName);
        $this->assertArrayHasKey($producer->getQueueUrl(), $queueManager->executeAction('list-available'));
    }

    public function testQueueInfo()
    {
        $queueName = $this->CreateQueue();
        $queueManager = $this->getQueueManager($queueName);
        $this->assertArrayHasKey('QueueArn', $queueManager->executeAction('info'));
    }

    public function testQueuePurge()
    {
        $queueName = $this->CreateQueue();
        $queueManager = $this->getQueueManager($queueName);
        $this->assertInternalType('array', $queueManager->executeAction('purge'));
    }

    public function testQueueDelete()
    {
        $queueName = $this->CreateQueue();
        $this->assertInternalType('array', $this->removeQueue($queueName));
    }
}
