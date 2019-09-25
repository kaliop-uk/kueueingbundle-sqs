
# Ver 0.7

* FIXED: Issue #3 Consumer process does not allow to use a long timeout interval
    It is now possible to use both a long timeout and/or a big number ofmessages to be consumed in calls to `consume`.
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
