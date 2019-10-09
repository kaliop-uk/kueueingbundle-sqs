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
        if (getenv('TRAVIS_JOB_NUMBER') != '') {
            $this->markTestSkipped('This test is known to fail when run in parallel, such as on Travis...');
        }

        $queueName = $this->CreateQueue();
        $queueManager = $this->getDriver()->getQueueManager($queueName);
        $producer = $this->getDriver()->getProducer($queueName);
        $queueUrl = $producer->getQueueUrl();
        // try to work around parallel execution in Travis...
        /*for ($i = 0; $i < 6; $i++) {
            $availableQueues = $queueManager->executeAction('list-available');
            if (isset($availableQueues[$queueUrl])) {
                break;
            }
            sleep(10);
        }*/
        $this->assertArrayHasKey($queueUrl, $queueManager->executeAction('list-available'));
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
