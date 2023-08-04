<?php

namespace DachcomBundle\Test\Unit\Config;

use Codeception\Exception\ModuleException;
use Dachcom\Codeception\Support\Test\BundleTestCase;
use I18nBundle\Configuration\Configuration;

class ConfigurationTest extends BundleTestCase
{
    /**
     * @throws ModuleException
     */
    public function testConfigArrayGetter()
    {
        $configuration = $this->getContainer()->get(Configuration::class);
        $adminConfig = $configuration->getConfigNode();

        $this->assertIsArray($adminConfig);
        $this->assertArrayHasKey('mode', $adminConfig);
        $this->assertArrayHasKey('locale_adapter', $adminConfig);
        $this->assertArrayHasKey('default_locale', $adminConfig);
        $this->assertArrayHasKey('translations', $adminConfig);

        $this->assertEquals('language', $adminConfig['mode']);
        $this->assertEquals('system', $adminConfig['locale_adapter']);
        $this->assertEquals('en', $adminConfig['default_locale']);
    }
}
