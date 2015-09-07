<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\ProducerInterface;
use Aws\Sqs\SqsClient;

/**
 * @todo add support for batch sending
 */
class Producer implements ProducerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $debug;
    protected $contentType = 'text/plain';
    // The message attribute used to store content-type. To be kept in sync with the Consumer
    protected $contentTypeAttribute = 'contentType';

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
     * @param string $queueName
     * @return Producer
     * @todo test that we can successfully send messages to 2 queues using the same SqsClient
     */
    public function setQueueName($queueName)
    {
        $this->queueUrl = $queueName;

        return $this;
    }

    /**
     * Publishes the message and does nothing with the properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     *
     * @todo support custom message attributes
     * @todo support custom delaySeconds
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        $this->client->sendMessage(array_merge(
            array(
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => $msgBody,
            ),
            $this->getClientParams($additionalProperties)
        ));
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
     * @param array $additionalProperties
     * @return array
     */
    protected function getClientParams(array $additionalProperties = array())
    {
        return array(
            'MessageAttributes' => array(
                $this->contentTypeAttribute => array('StringValue' => $this->contentType, 'DataType' => 'String')
            )
        );
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
