# Kaliop Queueing Bundle - AMAZON SQS plugin

Adds support for AWS Simple Queueing Service to the Kaliop Queueing Bundle

See: http://aws.amazon.com/sqs/ and https://github.com/kaliop-uk/kueueingbundle respectively.

It has been given its own bundle because it has higher requirements than the base Queueing Bundle


## Installation

1. Install the bundle via Composer.

2. Enable the KaliopQueueingPluginsSQSBundle bundle in your kernel class registerBundles().

3. Clear all caches if not on a dev environment


## Usage

4. If you do not have an AWS account, sign up for one at http://aws.amazon.com/

5. Create an SQS queue, using the web interface: https://console.aws.amazon.com/sqs/home

6. Set up configuration according to your AWS account

    - edit parameters.yml in this bundle

7. check that you can list the queue:

        php app/console kaliop_queueing:managequeue list -isqs

        php app/console kaliop_queueing:managequeue info -isqs <queue>

8. push a message to the queue

        php app/console kaliop_queueing:queuemessage -isqs <queue> <jsonpayload>

9. receive messages from the queue

        php app/console kaliop_queueing:consumer -isqs <queue>


## Running tests

If you want to run the testsuite outside of Travis, you will need to

1. have an AWS SQS account

2. set the following environment variables: `SYMFONY__SQS__KEY` `SYMFONY__SQS__SECRET` (note that the test config at
   the moment hardcodes usage of the us-east-1 region)

3. run `phpunit Tests/phpunit`


## Notes

* SQS does *not* natively support routing-keys the way that RabbitMQ does, nor the exchange/queue topology split.
    This bundle *does* add back support for routing-keys, but it is far from ideal; you are encouraged to set up
    multiple queues instead of using a single queue with multiple consumers which only consumed messages based on
    routing keys, esp. if you transmit massive amounts of messages in parallel.
    
    The way the bundle supports routing keys is:

    - if the Producer has a routing key set, it will add it to the Message Attributes when sending a Message
    - every Consumer always asks for all messages available in the Queue
    - if the Consumer has a routing key set, and the the message has one in its Message Attributes, the two are matched
    - in case of a match, standard processing goes on: the Consumer sends an ACK call to SQS to signal message reception
    - in case of no match, the Consumer does not send the ACK request to SQS; SQS will then wait for a little while, then
      put the message back in the queue (the amount of time it waits can be configured per-queue)

    If you find any discrepancy between the way routing keys are matched by RabbitMQ and by this bundle, please report
    it as a bug.

* SQS does *not* support setting a per-message TTL, only a per-queue one, so all MessageProducers which do have a TTL
    parameter in their public methods will just ignore it when being used with the SQS driver

* SQS does *not* guarantee that messages are delivered in the same order they are sent.
    If such a constraint is important, build monotonically increasing message IDs in your app, and manage them.

* SQS does guarantee that messages are delivered, but it does *not* guarantee that every message is delivered only once.
    If such a constraint is important, build unique message IDs in your app, and manage them. 

* For a more in-depth comparison of SQS and RabbitMQ, see f.e. http://blog.turret.io/rabbitmq-vs-amazon-sqs-a-short-comparison/


[![License](https://poser.pugx.org/kaliop/queueingbundle-sqs/license)](https://packagist.org/packages/kaliop/queueingbundle-sqs)
[![Latest Stable Version](https://poser.pugx.org/kaliop/queueingbundle-sqs/v/stable)](https://packagist.org/packages/kaliop/queueingbundle-sqs)
[![Total Downloads](https://poser.pugx.org/kaliop/queueingbundle-sqs/downloads)](https://packagist.org/packages/kaliop/queueingbundle-sqs)

[![Build Status](https://travis-ci.org/kaliop-uk/kueueingbundle-sqs.svg?branch=master)](https://travis-ci.org/kaliop-uk/queueingbundle-sqs)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/109e3d15-bfa6-4923-8077-2a3efa5be8b9/mini.png)](https://insight.sensiolabs.com/projects/109e3d15-bfa6-4923-8077-2a3efa5be8b9)
