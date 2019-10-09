<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

interface MessageDeduplicationIdCalculatorInterface
{
    /**
     * @param $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @return string
     */
    public function getMessageId($msgBody, $routingKey = '', array $additionalProperties = array());
}
