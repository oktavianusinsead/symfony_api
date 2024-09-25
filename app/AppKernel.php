<?php

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends BaseKernel
{
    
    public function __construct($environment, $debug)
    {
        date_default_timezone_set('UTC');

        parent::__construct($environment, $debug);
    }

    public function getRootDir()
    {
        return __DIR__;
    }
    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }
    public function getLogDir(): string
    {
        return dirname(__DIR__).'/var/logs';
    }


    public function registerBundles(): iterable
    {
        $bundles = [new Symfony\Bundle\FrameworkBundle\FrameworkBundle(), new Symfony\Bundle\SecurityBundle\SecurityBundle(), new Symfony\Bundle\TwigBundle\TwigBundle(), new Symfony\Bundle\MonologBundle\MonologBundle(), new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(), new esuite\MIMBundle\MIMBundle(), new esuiteSSOBundle\esuiteSSOBundle(), new FOS\RestBundle\FOSRestBundle(), new JMS\SerializerBundle\JMSSerializerBundle(), new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(), new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(), new esuite\MIMSeedFixturesBundle\MIMSeedFixturesBundle(), new Snc\RedisBundle\SncRedisBundle(), new LightSaml\SymfonyBridgeBundle\LightSamlSymfonyBridgeBundle(), new LightSaml\SpBundle\LightSamlSpBundle(), new Nelmio\ApiDocBundle\NelmioApiDocBundle()];

        if (in_array($this->getEnvironment(), ['dev', 'test', 'bb'])) {
            $devBundles = [new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(), new esuite\MIMLoggingBundle\MIMLoggingBundle()];

            $bundles = array_merge($bundles, $devBundles);
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
