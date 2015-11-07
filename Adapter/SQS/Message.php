<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\MessageInterface;

class Message implements MessageInterface
{
    protected $body;
    protected $properties = array();
    protected $contentType = 'application/json';
    protected $queueName;

    public function __construct($body, array $properties = array(), $contentType = null, $queueName = '')
    {
        $this->body = $body;
        $this->properties = $properties;
        if ($contentType != null) {
            $this->contentType = $contentType;
        }
        $this->queueName = $queueName;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Check whether a property exists in the 'properties' dictionary
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function get($name)
    {
        return $this->properties[$name];
    }

    /**
     * Returns the properties content
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
