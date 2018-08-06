<?php

namespace I18nBundle;

use I18nBundle\Tool\Install;
use I18nBundle\DependencyInjection\Compiler\RedirectorAdapterPass;
use I18nBundle\DependencyInjection\Compiler\LocaleAdapterPass;
use I18nBundle\DependencyInjection\Compiler\ContextAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class I18nBundle extends AbstractPimcoreBundle
{
    const BUNDLE_VERSION = '2.3.2';

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RedirectorAdapterPass());
        $container->addCompilerPass(new LocaleAdapterPass());
        $container->addCompilerPass(new PathGeneratorAdapterPass());
        $container->addCompilerPass(new ContextAdapterPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return self::BUNDLE_VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstaller()
    {
        return $this->container->get(Install::class);
    }
}
