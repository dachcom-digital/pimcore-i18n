<?php

namespace DachcomBundle\Test\unit\Config;

use DachcomBundle\Test\Test\DachcomBundleTestCase;
use I18nBundle\Configuration\Configuration;

class ConfigurationTest extends DachcomBundleTestCase
{
    /**
     * @throws \Codeception\Exception\ModuleException
     */
    public function testConfigArrayGetter()
    {
        $configuration = $this->getContainer()->get(Configuration::class);
        $adminConfig = $configuration->getConfigNode();

        $this->assertInternalType('array', $adminConfig);
        $this->assertArrayHasKey('mode', $adminConfig);
        $this->assertArrayHasKey('locale_adapter', $adminConfig);
        $this->assertArrayHasKey('default_locale', $adminConfig);
        $this->assertArrayHasKey('translations', $adminConfig);
    }
}
