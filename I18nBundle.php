<?php

namespace I18nBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class I18nBundle extends AbstractPimcoreBundle
{
    /**
     * {@inheritdoc}
     */
    public function getInstaller()
    {
        return $this->container->get('i18n.tool.installer');
    }
}