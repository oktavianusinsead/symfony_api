<?php

namespace Insead\MIMLoggingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class MIMLoggingExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['MonologBundle'])) {
            $config = ['handlers' => ['mimrequestlog' => ['type' => 'stream', 'path' => '%kernel.logs_dir%/api.log', 'channels' => 'mim_request_logging', 'formatter' => 'mim_request_formatter'], 'mimpayloadlog' => ['type' => 'stream', 'path' => '%kernel.logs_dir%/api.log', 'channels' => 'mim_payload_logging', 'formatter' => 'mim_payload_formatter'], 'applog' => ['channels' => ['!request', '!security', '!mim_request_logging', '!mim_payload_logging']]]];

            $container->prependExtensionConfig('monolog', $config);
        }
    }
}
