# Ver XXX

* NEW: the bundle is now compatible with FIFO queues
    In order to send messages to a FIFO queue, set the `message_group_id` in the queue_options configuration

* NEW: it is now possible to set custom parameters for the `sendMessage` call via the Producer's `publish` method


# Ver 0.9

* NEW: the bundle is now compatible with Symfony versions all the way up to 4.3


# Ver 0.8

* NEW: it is now possible to set more configuration options for consumers via settings: `max_messages_per_request`,
    `request_timeout`, `polling_interval` and `gc_probability`

* FIXED: removed one leftover echo debug statement

* FIXED: the Consumer does now honour the requestTimeout and requestBatchSize parameters when the `consume()` method
    is called with a max amount of messages to retrieve and/or max time to run.
    In version 0.7, passing non-0 values for those parameters would force the consumer to use long polling with 20
    secs timeout and 10 messages batch-size per call. 


# Ver 0.7

* FIXED: Issue #3 Consumer process does not allow to use a long timeout interval
    It is now possible to use both a long timeout and/or a big number of messages to be consumed in calls to `consume`.
    The client will take care not to exceed in any case the AWS limits (20 secs timeout, 10 messages per call), and
    keep polling as long as one of the limits is reached


# Ver 0.6

* FIXED: Issue #1 Memory leak when running a Consumer process
    The Consumer will now trigger the php garbage collector every few iterations of the `maybeStopConsumer` method.
    This should avoid the known memory leaks in the AWS SDK from blowing up your server.
    As is tradition in php, the frequency with which the garbage collection is triggered is configured via a percentage
    setting. 

* FIXED: Issue #2 consume() method does not respect the $amount parameter and returns immediately if there are no messages
    The Consumer did not wait to receive 10 messages when `consumer(10, 0)` was called and there were fewer than 10
    messages in the queue.


# Ver 0.5

* NEW: made the package compatible with Symfony 3. As a side effect, we do not support Symfony 2.3 any more.

* NEW: add support for memory limit in the consumer


# Ver 0.4

* NEW: support proper consumer shutdown when receiving unix signals


# Ver 0.3

* NEW: introduce compatibility with queueingbundle 0.4


# Ver 0.2.1

* FIXED: php error when received messages miss the content-type attribute


# Ver 0.2

* first release announced to the world
