<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * A minimalist Kernel used to run functional tests
 */
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),

            //new Symfony\Bundle\TwigBundle\TwigBundle(),
            //new Symfony\Bundle\MonologBundle\MonologBundle(),
            //new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            //new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new Kaliop\QueueingBundle\KaliopQueueingBundle(),

            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new Kaliop\Queueing\Plugins\SQSBundle\KaliopQueueingPluginsSQSBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/KaliopQueueingSQSBundleTests/cache';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/KaliopQueueingSQSBundleTests/logs';
    }
}