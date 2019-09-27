<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Kaliop\QueueingBundle\Queue\SignalHandlingConsumerInterface;
use Kaliop\QueueingBundle\Adapter\ForcedStopException;
use Aws\Sqs\SqsClient;
use Aws\TraceMiddleware;
use Psr\Log\LoggerInterface;

/**
 * @todo support using short polling even when given a total timeout - even though it will complicate consume() even more than it already is
 */
class Consumer implements ConsumerInterface, SignalHandlingConsumerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $queueName;
    protected $callback;

    protected $routingKey;
    protected $routingKeyRegexp;
    protected $logger;
    // The message attribute used to store content-type. To be kept in sync with the Producer
    protected $contentTypeAttribute = 'contentType';
    protected $routingAttribute = 'routingKey';
    protected $debug = false;
    protected $forceStop = false;
    protected $forceStopReason;
    protected $dispatchSignals = false;
    protected $memoryLimit = null;
    /** @var int $requestBatchSize how many messages to receive in each poll by default */
    protected $requestBatchSize = 1;
    /** @var int $requestTimeout how long to wait for messages in each request. Switches between long and short polling */
    protected $requestTimeout = 0;
    /** @var int the minimum interval between two queue polls - in milliseconds */
    protected $pollingIntervalMs = 200000;
    /** @var int $gcProbability the probability of calling gc_collect_cycles at the end of every poll */
    protected $gcProbability = 1;

    const MAX_MESSAGES_PER_REQUEST = 10;
    const MAX_REQUEST_TIMEOUT = 20;

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
     * @param int $limit MB
     * @return Consumer
     */
    public function setMemoryLimit($limit)
    {
        $this->memoryLimit = $limit;

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

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

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

    public function setRequestTimeout($timeout)
    {
        $this->requestTimeout = $timeout;

        return $this;
    }

    public function setPollingInterval($intervalMs)
    {
        $this->pollingIntervalMs = $intervalMs;

        return $this;
    }

    public function setGCProbability($probability)
    {
        $this->gcProbability = $probability;

        return $this;
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#receivemessage
     *
     * @param int $amount 0 for unlimited
     * @param int $timeout seconds 0 for unlimited. NB: any value > 0 activates 'long polling' mode
     * @return void
     */
    public function consume($amount, $timeout = 0)
    {
        if ($timeout > 0) {
            $endTime = time() + $timeout;
            $remainingTime = $timeout;
        }

        $received = 0;

        $receiveParams = array(
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => array('All'),
            'MessageAttributeNames' => array('All')
        );

        while(true) {
            $reqTime = microtime(true);

            if ($timeout > 0) {
                $wait = $remainingTime;
                if ($wait > static::MAX_REQUEST_TIMEOUT) {
                    $wait = static::MAX_REQUEST_TIMEOUT;
                }
            } else {
                $wait = $this->requestTimeout;
            }

            if ($wait > 0) {
                // according to the spec, this is maximum wait time. If messages are available sooner, they get delivered immediately
                $receiveParams['WaitTimeSeconds'] = $wait;
            } else {
                if (isset($receiveParams['WaitTimeSeconds'])) {
                    unset($receiveParams['WaitTimeSeconds']);
                }
            }

            if ($amount > 0) {
                $limit = $amount - $received;

                if ($limit >= static::MAX_MESSAGES_PER_REQUEST) {
                    $limit = static::MAX_MESSAGES_PER_REQUEST;
                }
            } else {
                $limit = $this->requestBatchSize;
            }

            $receiveParams['MaxNumberOfMessages'] = $limit;

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

                    $received++;

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

            $this->maybeStopConsumer();

            if ($amount > 0 && $received >= $amount) {
                return;
            }

            if ($timeout > 0 && ($remainingTime = ($endTime - time())) <= 0) {
                return;
            }

            // observe MAX 5 requests per sec per queue by default: sleep for 0.2 secs in between requests
            $passedMs = (microtime(true) - $reqTime) * 1000000;
            if ($passedMs < $this->pollingIntervalMs) {
                usleep($this->pollingIntervalMs - $passedMs);
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

    public function setHandleSignals($doHandle)
    {
        $this->dispatchSignals = $doHandle;
    }


    public function forceStop($reason = '')
    {
        $this->forceStop = true;
        $this->forceStopReason = $reason;
    }

    /**
     * Dispatches signals and throws an exception if user wants to stop. To be called at execution points when there is no data loss
     *
     * @throws ForcedStopException
     */
    protected function maybeStopConsumer()
    {
        if ($this->dispatchSignals) {
            pcntl_signal_dispatch();
        }

        if ($this->gcProbability > 0 && rand(1, 100) <= $this->gcProbability) {
            gc_collect_cycles();
        }

        if ($this->memoryLimit > 0 && !$this->forceStop && memory_get_usage(true) >= ($this->memoryLimit * 1024 * 1024) ) {
            $this->forceStop("Memory limit of {$this->memoryLimit} MB reached while consuming messages");
        }

        if ($this->forceStop) {
            throw new ForcedStopException($this->forceStopReason);
        }
    }
}
