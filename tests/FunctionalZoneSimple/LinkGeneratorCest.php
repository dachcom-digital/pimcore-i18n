<?php

namespace DachcomBundle\Test\FunctionalZoneSimple;

use DachcomBundle\Test\Support\FunctionalTester;

class LinkGeneratorCest
{
    public function testObjectLinkGenerator(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test');

        $classDefinition = $I->haveAPimcoreClass('TestClass');
        $object = $I->haveAPimcoreObject($classDefinition->getName(), 'object-1');

        // to prevent pimcores static route caching
        $routeName = sprintf('test_route_%s', uniqid('', false));
        $object->setProperty('dynamic_static_route_name', 'input', $routeName);

        $I->haveAPageDocumentForSite($site, 'de', [], 'de');

        $I->haveAStaticRoute($routeName, [
            'pattern'   => '/(\w+)\\/special-route\\/(.*?)$/',
            'reverse'   => '/{%_locale}/special-route/%object_id',
            'action'    => 'defaultAction',
            'variables' => '_locale,object_id',
        ]);

        $url = $I->haveAI18nGeneratedLinkForElement(
            $object,
            ['_locale' => 'de'],
            ['site' => $site]
        );

        $I->assertSame($url, sprintf('http://test-domain1.test/de/special-route/%d', $object->getId()));
    }

    public function testObjectLinkGeneratorWithInvalidSiteLocale(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test', [], 'de');

        $classDefinition = $I->haveAPimcoreClass('TestClass');
        $object = $I->haveAPimcoreObject($classDefinition->getName(), 'object-1');

        // to prevent pimcores static route caching
        $routeName = sprintf('test_route_%s', uniqid('', false));
        $object->setProperty('dynamic_static_route_name', 'input', $routeName);

        $I->haveAPageDocumentForSite($site, 'de', [], 'de');

        $I->haveAStaticRoute($routeName, [
            'pattern'   => '/(\w+)\\/special-route\\/(.*?)$/',
            'reverse'   => '/{%_locale}/special-route/%object_id',
            'action'    => 'defaultAction',
            'variables' => '_locale,object_id',
        ]);

        $url = $I->haveAI18nGeneratedLinkForElement(
            $object,
            ['_locale' => 'de'],
            ['site' => $site]
        );

        $I->assertSame($url, sprintf('http://test-domain1.test/special-route/%d', $object->getId()));
    }
}