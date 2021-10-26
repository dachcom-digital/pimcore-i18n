<?php

namespace I18nBundle;

use I18nBundle\DependencyInjection\Compiler\ContextAdapterPass;
use I18nBundle\DependencyInjection\Compiler\LocaleProviderAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use I18nBundle\DependencyInjection\Compiler\RedirectorAdapterPass;
use I18nBundle\Tool\Install;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class I18nBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/i18n';

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RedirectorAdapterPass());
        $container->addCompilerPass(new LocaleProviderAdapterPass());
        $container->addCompilerPass(new PathGeneratorAdapterPass());
        $container->addCompilerPass(new ContextAdapterPass());
    }

    public function getInstaller(): Install
    {
        return $this->container->get(Install::class);
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
