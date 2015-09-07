<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\Kinesis;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use InvalidArgumentException;
use Kaliop\QueueingBundle\Queue\Queue;
use Kaliop\QueueingBundle\Queue\QueueManagerInterface;

/**
 * A class dedicated to sending control commands
 *
 * @todo add support for queue
 *
 * @see http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Sqs.SqsClient.html
 */
class QueueManager implements ContainerAwareInterface, QueueManagerInterface
{
    protected $streamName;
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $queue
     * @return QueueManager
     */
    public function setQueueName($queue)
    {
        $this->streamName = $queue;

        return $this;
    }

    public function listActions()
    {
        return array('info', 'list', 'delete');
    }

    public function executeAction($action)
    {
        switch ($action) {
            case 'delete':
                return $this->deleteQueue();

            case 'info':
                return $this->queueInfo();

            case 'list':
                return $this->listQueues();

            default:
                throw new InvalidArgumentException("Action $action not supported");
        }
    }

    protected function deleteQueue()
    {
        $result = $this->getProducerService()->call('DeleteQueue', array('QueueUrl' => $this->streamName));
        return $result['@metadata'];
    }

    protected function queueInfo()
    {
        $result = $this->getProducerService()->call('getQueueAttributes', array('QueueUrl' => $this->streamName));
        return $result->get('Attributes');
    }

    protected function listQueues()
    {
        $result = $this->getProducerService()->call('listQueues');
        return $result->get('QueueUrls');
    }

    protected function getProducerService()
    {
        return $this->container->get('kaliop_queueing.sqs.producer');
    }
}
