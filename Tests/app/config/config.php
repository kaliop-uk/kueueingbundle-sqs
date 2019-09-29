<?php
// Used to set parameters based on env vars, regardless of the SF version in use

// these environment variables are stored encrypted in travis.yml
$container->setParameter('sqs_key', getenv('SYMFONY__SQS__KEY'));
$container->setParameter('sqs_secret', getenv('SYMFONY__SQS__SECRET'));
