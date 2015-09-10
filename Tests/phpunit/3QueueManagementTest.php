<?php

require_once(__DIR__.'/SQSTest.php');

class QueueManagementTests extends SQSTest
{
    public function testListQueues()
    {
        $queueManager = $this->getDriver()->getQueueManager(null);
        $this->asserContains($this->queueName, $queueManager->executeAction('list'));
    }
}