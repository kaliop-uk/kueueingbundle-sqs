<?php

namespace Kaliop\Queueing\Plugins\SQSBundle\Adapter\SQS;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use InvalidArgumentException;
use Kaliop\QueueingBundle\Queue\Queue;
use Kaliop\QueueingBundle\Queue\QueueManagerInterface;

/**
 * A class dedicated to sending control commands
 *
 * @todo add support for listing configured queues
 *
 * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Sqs.SqsClient.html
 */
class QueueManager implements ContainerAwareInterface, QueueManagerInterface
{
    protected $queueName;
    protected $container;
    protected $registeredQueues = array();

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
        $this->queueName = $queue;

        return $this;
    }

    public function listActions()
    {
        return array('list-available', 'list-configured', 'create', 'info', 'purge', 'delete');
    }

    public function executeAction($action, array $arguments=array())
    {
        switch ($action) {
            case 'list-available':
                return $this->listAvailableQueues();

            case 'list-configured':
                return $this->listConfiguredQueues();

            case 'create':
                return $this->createQueue($arguments);

            case 'info':
                return $this->queueInfo();

            case 'purge':
                return $this->purgeQueue();

            case 'delete':
                return $this->deleteQueue();

            default:
                throw new InvalidArgumentException("Action $action not supported");
        }
    }

    /**
     * @return array keys are the queue names, values the queue type
     */
    protected function listAvailableQueues()
    {
        $result = $this->getProducerService()->call('listQueues');
        $result = $result->get('QueueUrls');
        // make this slightly easier to understand by callers
        if ($result === null) {
            $result = array();
        }
        return array_combine($result, array_fill(0, count($result), Queue::TYPE_ANY));
    }

    protected function listConfiguredQueues()
    {
        if (count($this->registeredQueues) == 0) {
            return array();
        }
        return array_combine($this->registeredQueues, array_fill(0, count($this->registeredQueues), Queue::TYPE_ANY));
    }

    /**
     * NB: works only after the relevant config has been set in yml, which makes it less than ideal...
     *     See how the SQSTest does to dynamically create both the queue on the broker and the producer + consumer
     * @param array $args allowed elements: see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#createqueue
     * @return string the queue Url
     * @throw \Exception on failure
     */
    protected function createQueue($args)
    {
        $result = $this->getProducerService()->call(
            'CreateQueue',
            array(
                'QueueName' => $this->queueName,
                'Attributes' => $args
            )
        );
        return $result->get('QueueUrl');
    }

    /**
     * @return array
     * @throw \Exception on failure
     */
    protected function queueInfo()
    {
        $producer = $this->getProducerService();
        $result = $producer->call('getQueueAttributes', array('QueueUrl' => $producer->getQueueUrl(), 'AttributeNames' => array('All')));
        return $result->get('Attributes');
    }

    /**
     * @return array
     * @throw \Exception on failure
     */
    protected function purgeQueue()
    {
        $producer = $this->getProducerService();
        $result = $producer->call('PurgeQueue', array('QueueUrl' => $producer->getQueueUrl()));
        return $result['@metadata'];
    }

    /**
     * @return array
     * @throw \Exception on failure
     */
    protected function deleteQueue()
    {
        $producer = $this->getProducerService();
        $result = $producer->call('DeleteQueue', array('QueueUrl' => $producer->getQueueUrl()));
        return $result['@metadata'];
    }

    protected function getProducerService()
    {
        return $this->container->get('kaliop_queueing.sqs.'. $this->queueName . '_producer');
    }

    public function registerQueue($queueName)
    {
        $this->registeredQueues[]=$queueName;
    }
}
