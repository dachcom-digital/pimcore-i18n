<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle;

use I18nBundle\DependencyInjection\Compiler\LocaleProviderAdapterPass;
use I18nBundle\DependencyInjection\Compiler\PathGeneratorAdapterPass;
use I18nBundle\DependencyInjection\Compiler\RedirectorAdapterPass;
use I18nBundle\DependencyInjection\Compiler\RouterPass;
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
        $container->addCompilerPass(new RouterPass());
        $container->addCompilerPass(new RedirectorAdapterPass());
        $container->addCompilerPass(new LocaleProviderAdapterPass());
        $container->addCompilerPass(new PathGeneratorAdapterPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
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
