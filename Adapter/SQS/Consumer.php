<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Kaliop\Queueing\Plugins\KinesisBundle\Service\SequenceNumberStoreInterface;
use \Aws\Sqs\SqsClient;

/**
 * @todo support long polling
 */
class Consumer implements ConsumerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $callback;
    protected $requestBatchSize = 1;
    // The message attribute used to store content-type. To be kept in sync with the Producer
    protected $contentTypeAttribute = 'contentType';

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
            $reqTime = microtime(true);
            $result = $this->client->receiveMessage(array(
                'QueueUrl' => $this->queueUrl,
                'MaxNumberOfMessages' => $limit,
                'AttributeNames' => array('All'),
                'MessageAttributeNames' => array('All')
            ));

            $messages = $result->get('Messages');

            if (is_array($messages)) {
                foreach($messages as $message) {

                    // removing the message from the queue is manual with SQS
                    $this->client->deleteMessage(array(
                        'QueueUrl' => $this->queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle']
                    ));

                    $data = $message['Body'];
                    unset($message['Body']);

                    $this->callback->receive(new Message(
                        $data,
                        $message,
                        $message['MessageAttributes'][$this->contentTypeAttribute]['StringValue'])
                    );
                }
            }

            if ($amount > 0) {
                return;
            }

            /// @todo use a parameter to decide the polling interval
            // observe MAX 5 requests per sec per queue: sleep for 0.2 secs in between requests
            $passedMs = (microtime(true) - $reqTime) * 1000000;
            if ($passedMs < 200000) {
                usleep(200000 - $passedMs);
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
