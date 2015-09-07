<?php

namespace Kaliop\Queueing\Plugins\KinesisBundle\Adapter\Kinesis;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Kaliop\Queueing\Plugins\KinesisBundle\Service\SequenceNumberStoreInterface;
use \Aws\Sqs\SqsClient;

class Consumer implements ConsumerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $callback;
    protected $requestBatchSize = 1;

    public function __construct(array $config)
    {
        $this->client = new SqsClient($config);
    }

    /**
     * Does nothing
     * @param int $limit
     * @return Consumer
     */
    public function setMemoryLimit($limit)
    {
        return $this;
    }

    /**
     * Does nothing
     * @param string $key
     * @return Consumer
     */
    public function setRoutingKey($key)
    {
        return $this;
    }

    /**
     * @param MessageConsumerInterface $callback
     * @return Consumer
     */
    public function setCallback(MessageConsumerInterface $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * The number of messages to download in every request to the Kinesis API.
     * Bigger numbers are better for performances, but there is a limit on the size of the response which Kinesis will send.
     * @param int $amount
     * @return Consumer
     */
    public function setRequestBatchSize($amount)
    {
        $this->requestBatchSize = $amount;

        return $this;
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Kinesis.SqsClient.html#_getRecords
     * Will throw an exception if $amount is > 10.000
     *
     * @param int $amount
     * @return nothing
     */
    public function consume($amount)
    {
        $limit = ($amount > 0) ? $amount : $this->requestBatchSize;

        while(true) {
            $result = $this->client->receiveMessage(array(
                'QueueUrl' => $this->queueUrl,
                'MaxNumberOfMessages' => $limit,
            ));

            $records = $result->get('Records');

/// @todo...
            foreach($records as $record) {
                $data = $record['Data'];
                unset($record['Data']);
                $this->callback->receive(new Message($data, $record));
            }

            if ($amount > 0) {
                return;
            }
        }
    }

    /**
     * @param string $queueName
     * @return Consumer
     */
    public function setQueueName($queueName)
    {
        $this->queueUrl = $queueName;

        return $this;
    }
}
