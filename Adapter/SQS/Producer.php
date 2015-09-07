<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\ProducerInterface;
use Aws\Sqs\SqsClient;

class Producer implements ProducerInterface
{
    /** @var  \Aws\Sqs\SqsClient */
    protected $client;
    protected $queueUrl;
    protected $debug;

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
     * Note that this has less effect than passing a 'debug' option in constructor, as it will be
     * only used by publish() from now on
     *
     * @param bool $debug use null for 'undefined'
     * @return Producer
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Publishes the message and does nothing with the properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        $result = $this->client->sendMessage(array_merge(
            array(
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => $msgBody
            ),
            $this->getClientParams()
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
     * @return array
     */
    protected function getClientParams()
    {
///@todo
        if ($this->debug !== null) {
            return array('@http' => array('debug' => $this->debug));
        }

        return array();
    }

    /**
     * @param string $contentType
     * @return Producer
     * @throws \Exception if unsupported contentType is used
     *
     * @todo allow different serializations - but the SQS protocol does not allow to store the content type in the
     *       message natively, so we should have to 'invent' an encapsulation format...
     */
    public function setContentType($contentType)
    {
        if($contentType != 'application/json') {
            throw new \Exception("Unsupported content-type for message serialization: $contentType. Only 'application/json' is supported");
        }

        return $this;
    }
}
