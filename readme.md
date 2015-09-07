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
 
7. check that you can list the stream, and the shards in it:
 
        php app/console kaliop_queueing:managequeue list -bsqs
        
        php app/console kaliop_queueing:managequeue info -bsqs <queue>

8. push a message to the stream 

        php app/console kaliop_queueing:queuemessage -bsqs <queue> <jsonpayload>
        
9. receive messages from the stream

        php app/console kaliop_queueing:consumer -bsqs <queue>


## Notes

* SQS does *not* guarantee that messages are delivered in the same order they are sent.
    If such a constraint is important, build monotonically increasing message IDs in your app, and manage them.

* SQS does guarantee that messages are delivered, but it does *not* guarantee that every message is delivered only once.
    If such a constraint is important, build unique message IDs in your app, and manage them. 


[![License](https://poser.pugx.org/kaliop/queueingbundle-sqs/license)](https://packagist.org/packages/kaliop/queueingbundle-sqs)
[![Latest Stable Version](https://poser.pugx.org/kaliop/queueingbundle-sqs/v/stable)](https://packagist.org/packages/kaliop/queueingbundle-sqs)
[![Total Downloads](https://poser.pugx.org/kaliop/queueingbundle-sqs/downloads)](https://packagist.org/packages/kaliop/queueingbundle-sqs)

[![Build Status](https://travis-ci.org/kaliop-uk/queueingbundle-sqs.svg?branch=master)](https://travis-ci.org/kaliop-uk/queueingbundle-sqs)
