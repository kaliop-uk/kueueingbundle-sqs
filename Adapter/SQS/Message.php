<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Queue\MessageInterface;

class Message implements MessageInterface
{
    protected $body;
    protected $properties = array();
    protected $contentType;

    public function __construct($body, array $properties = array(), $contentType='application/json')
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->contentType = $contentType;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * This is hardcoded because
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
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
