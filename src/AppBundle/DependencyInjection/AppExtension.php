<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AppExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('app.mediaconch.address', $config['mediaconch']['address']);
        $container->setParameter('app.mediaconch.port', $config['mediaconch']['port']);
        $container->setParameter('app.mediaconch.api.version', $config['mediaconch']['api_version']);
        $container->setParameter('app.mediaconch.absolute_url_for_mail', $config['mediaconch']['absolute_url_for_mail']);
    }
}
