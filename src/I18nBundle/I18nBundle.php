<?php

namespace I18nBundle;

use I18nBundle\DependencyInjection\Compiler\ContextAdapterPass;
use I18nBundle\DependencyInjection\Compiler\LocaleAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use I18nBundle\DependencyInjection\Compiler\RedirectorAdapterPass;
use I18nBundle\Tool\Install;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class I18nBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'dachcom-digital/i18n';

    /**
     * {@inheritdoc}
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
    public function getInstaller()
    {
        return $this->container->get(Install::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
