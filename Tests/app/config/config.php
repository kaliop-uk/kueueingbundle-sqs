<?php
// Used to set parameters based on env vars, regardless of the SF version in use

// these environment variables are stored encrypted in travis.yml
$container->setParameter('sqs_key', getenv('sqs.key'));
$container->setParameter('sqs_secret', getenv('sqs.secret'));
