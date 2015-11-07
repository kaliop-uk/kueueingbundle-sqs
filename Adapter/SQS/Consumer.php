<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Aws\Sqs\SqsClient;
use Aws\TraceMiddleware;
use Psr\Log\LoggerInterface;

/**
 * @todo support long polling - even though it will complicate consume() even more than it already is
 */
class Consumer implements ConsumerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $queueName;
    protected $callback;
    protected $requestBatchSize = 1;
    protected $routingKey;
    protected $routingKeyRegexp;
    protected $logger;
    // The message attribute used to store content-type. To be kept in sync with the Producer
    protected $contentTypeAttribute = 'contentType';
    protected $routingAttribute = 'routingKey';
    protected $debug = false;

    public function __construct(array $config)
    {
        $this->client = new SqsClient($config);
    }

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Enabled debug. At the moment can not disable it
     * @param bool|array $debug
     * @return $this
     *
     * @todo test if using $handlerList->removeByInstance we can disable debug as well
     */
    public function setDebug($debug) {
        if ($debug == $this->debug) {
            return $this;
        }
        if ($debug) {
            $handlerList = $this->client->getHandlerList();
            $handlerList->interpose(new TraceMiddleware($debug === true ? [] : $debug));
        }

        return $this;
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
     * @param string $key
     * @return Consumer
     */
    public function setRoutingKey($key)
    {
        $this->routingKey = (string)$key;
        $this->routingKeyRegexp = '/'.str_replace(array('\*', '#'), array('[^.]*', '.*'), preg_quote($this->routingKey, '/')).'/';
        return $this;
    }

    /**
     * @param MessageConsumerInterface $callback
     * @return Consumer
     */
    public function setCallback($callback)
    {
        if (! $callback instanceof \Kaliop\QueueingBundle\Queue\MessageConsumerInterface) {
            throw new \RuntimeException('Can not set callback to SQS Consumer, as it is not a MessageConsumerInterface');
        }
        $this->callback = $callback;

        return $this;
    }

    /**
     * The number of messages to download in every request to the SQS API.
     * Bigger numbers are better for performances, but there is a limit on the size of the response which SQS will send.
     * @param int $amount
     * @return Consumer
     */
    public function setRequestBatchSize($amount)
    {
        $this->requestBatchSize = $amount;

        return $this;
    }

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#receivemessage
     * Will throw an exception if $amount is > 10.000
     *
     * @param int $amount
     * @param int $timeout seconds
     * @return nothing
     */
    public function consume($amount, $timeout=0)
    {
        $limit = ($amount > 0) ? $amount : $this->requestBatchSize;
        if ($timeout > 0) {
            $startTime = time();
            $remaining = $timeout;
        }

        $receiveParams = array(
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => $limit,
            'AttributeNames' => array('All'),
            'MessageAttributeNames' => array('All')
        );

        while(true) {
            $reqTime = microtime(true);

            if ($timeout > 0) {
                // according to the spec, this is maximum wait time. If messages are available sooner, they get delivered immediately
                $receiveParams['WaitTimeSeconds'] = $remaining;
            }

            $result = $this->client->receiveMessage($receiveParams);
            $messages = $result->get('Messages');

            if (is_array($messages)) {
                foreach($messages as $message) {

                    // How we implement routing keys with SQS: since it is not supported natively, we check if the route
                    // matches after having downloaded the message. If it does not match, we just skip processing it.
                    // Since we will not call deleteMessage, SQS will requeue the message in a short time.
                    // This is far from optimal, but it might be better than nothing
                    if (! $this->matchRoutingKey($message)) {
                        continue;
                    }

                    // removing the message from the queue is manual with SQS
                    $this->client->deleteMessage(array(
                        'QueueUrl' => $this->queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle']
                    ));

                    $data = $message['Body'];
                    unset($message['Body']);

                    $contentType = isset( $message['MessageAttributes'][$this->contentTypeAttribute]['StringValue'] ) ?
                        $message['MessageAttributes'][$this->contentTypeAttribute]['StringValue'] : '';

                    if ($contentType != '') {
                        $this->callback->receive(new Message($data, $message, $contentType, $this->queueName));
                    } else {
                        if ($this->logger) {
                            $this->logger->warning('The SQS Consumer received a message with no content-type attribute. Assuming default');
                        }

                        $this->callback->receive(new Message($data, $message, null, $this->queueName));
                    }
                }
            }

            if ($amount > 0) {
                return;
            }

            if ($timeout > 0 && ($remaining = ($startTime + $timeout - time())) <= 0) {
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
     * Adopt the RabbitMQ routing key algorithm:
     * - split on dots
     * - * matches one word (q: also empty ones?)
     * - # matches any words
     *
     * @todo the current implementation is naive and does probably not match RabbitMq if the routing key is something like aaa.*b.ccc
     *       A better implementation would probably involve usage of a trie
     *       Some pointers on how to implement it fast: http://lists.rabbitmq.com/pipermail/rabbitmq-discuss/2011-June/013564.html
     * @see setRoutingKey
     *
     * @param array $message
     * @return bool
     */
    protected function matchRoutingKey(array $message)
    {
        if ($this->routingKey === null || $this->routingKey === '') {
            return true;
        }
        if (!isset($message['MessageAttributes'][$this->routingAttribute]['StringValue'])) {
            if ($this->logger) {
                $this->logger->warning('The SQS Consumer has a routing key set, and it received a message without routing information. Processing it anyway');
            }
            return true;
        }

        return preg_match(
            $this->routingKeyRegexp,
            $message['MessageAttributes'][$this->routingAttribute]['StringValue']
        );
    }

    /**
     * @param string $queueUrl the complete queue name as used by SQS
     * @return Consumer
     */
    public function setQueueUrl($queueUrl)
    {
        $this->queueUrl = $queueUrl;

        return $this;
    }

    /**
     * @return string the complete queue name as used by SQS
     */
    public function getQueueUrl()
    {
        return $this->queueUrl;
    }
}
