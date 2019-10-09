<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Service\MessageDeduplicationIdCalculator;

use Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS\MessageDeduplicationIdCalculatorInterface;

/**
 * An extremely simple 'unique message id' generator, geared for demonstration more than anything else...
 */
class Sequence implements MessageDeduplicationIdCalculatorInterface
{
    protected $current = 0;
    protected $increment = 1;

    public function __construct($start = 0, $increment = 1)
    {
        $this->current = $start;
        $this->increment = $increment;
    }

    public function getMessageId($msgBody, $routingKey = '', array $additionalProperties = array())
    {
        $value = getmypid() . '_' . $this->current;
        $this->current += $this->increment;
        return $value;
    }
}
