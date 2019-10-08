<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\ProducerInterface;
use Aws\Sqs\SqsClient;
use Aws\TraceMiddleware;

class Producer implements ProducerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $debug = false;
    protected $contentType = 'text/plain';
    // The message attribute used to store content-type. To be kept in sync with the Consumer
    protected $contentTypeAttribute = 'contentType';
    protected $routingKeyAttribute = 'routingKey';
    protected $messageGroupId;

    /**
     * @param array $config - minimum seems to be: 'credentials', 'region', 'version'
     * @see \Aws\AwsClient::__construct for the full list
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
     */
    public function __construct(array $config)
    {
        $this->client = new SqsClient($config);
    }

    /**
     * Enabled debug. At the moment can not disable it
     *
     * @param $debug
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
     * @param string $queueName NB: complete queue name as used by SQS
     * @param string $queueUrl
     * @return Producer
     * @todo test that we can successfully send messages to 2 queues using the same SqsClient
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

    public function setMessageGroupId($messageGroupId)
    {
        $this->messageGroupId = $messageGroupId;

        return $this;
    }

    /**
     * Publishes the message and does nothing with the properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#sendmessage
     *
     * @todo support custom message attributes (possible via $additionalProperties)
     * @todo support custom delaySeconds (possible via $additionalProperties)
     * @todo support custom MessageDeduplicationId (possible via $additionalProperties)
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        $this->client->sendMessage(array_merge(
            array(
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => $msgBody,
            ),
            $this->getClientParams($routingKey, $additionalProperties)
        ));
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#sendmessagebatch
     * @param array[] $messages each element is an array that must contain:
     *                          - msgBody (string)
     *                          - routingKey (string, optional)
     *                          - additionalProperties (array, optional)
     */
    public function batchPublish(array $messages)
    {
        $j = 0;
        for ($i = 0; $i < count($messages); $i += 10) {
            $entries = array();
            $toSend = array_slice($messages, $i, 10);
            foreach($toSend as $message) {
                $entries[] = array_merge(
                    array(
                        'MessageBody' => $message['msgBody'],
                        'Id' => $j++
                    ),
                    $this->getClientParams(@$message['routingKey'], @$message['additionalProperties'])
                );
            }

            $result = $this->client->sendMessageBatch(
                array(
                    'QueueUrl' => $this->queueUrl,
                    'Entries' => $entries,
                )
            );

            if (($ok = count($result->get('Successful'))) != ($tot = count($toSend))) {
                throw new \RuntimeException("Batch sending of messages failed - $ok ok out of $tot");
            }
        }
    }

    /**
     * Allows callers to do whatever they want with the client - useful to the Queue Mgr
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function call($method, array $args = array())
    {
        return $this->client->$method(array_merge($args, $this->getClientParams()));
    }

    /**
     * Prepares the extra parameters to be injected into calls made via the SQS Client
     * @param string $routingKey
     * @param array $additionalProperties
     * @return array
     *
     * @todo shall we throw if $additionalProperties['expiration'] is set, since we don't support it ?
     */
    protected function getClientParams($routingKey = '', array $additionalProperties = array())
    {
        $result = array(
            'MessageAttributes' => array(
                $this->contentTypeAttribute => array('StringValue' => $this->contentType, 'DataType' => 'String'),
            )
        );
        if ($routingKey != '') {
            $result['MessageAttributes'][$this->routingKeyAttribute] = array('StringValue' => $routingKey, 'DataType' => 'String');
        }

        if ($this->messageGroupId != null) {
            $result['MessageGroupId'] = $this->messageGroupId;
        }

        $result = array_merge($result, $additionalProperties);

        return $result;
    }

    /**
     * @param string $contentType
     * @return Producer
     * @throws \Exception if unsupported contentType is used
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }
}
