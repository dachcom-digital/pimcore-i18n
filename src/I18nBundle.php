<?php

namespace I18nBundle;

use I18nBundle\DependencyInjection\Compiler\ContextAdapterPass;
use I18nBundle\DependencyInjection\Compiler\CountryAdapterPass;
use I18nBundle\DependencyInjection\Compiler\LanguageAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class I18nBundle extends AbstractPimcoreBundle
{
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
    public function getInstaller()
    {
        return $this->container->get('i18n.tool.installer');
    }
}