<?php

namespace I18nBundle;

use I18nBundle\Tool\Install;
use I18nBundle\DependencyInjection\Compiler\ContextAdapterPass;
use I18nBundle\DependencyInjection\Compiler\CountryAdapterPass;
use I18nBundle\DependencyInjection\Compiler\LanguageAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class I18nBundle extends AbstractPimcoreBundle
{
    const BUNDLE_VERSION = '2.1.3';

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CountryAdapterPass());
        $container->addCompilerPass(new LanguageAdapterPass());
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