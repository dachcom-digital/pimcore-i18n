<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\FunctionalTester;

class AlternateLinksCest extends AbstractZone
{
    public function staticRouteAlternateInZoneOneTest(FunctionalTester $I)
    {
        $this->setupSites($I);

        $I->haveAStaticRoute('test_route', [
            'pattern'   => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/',
            'reverse'   => '/{%_locale}/@testKey/%object_id',
            'action'    => 'defaultAction',
            'variables' => '_locale,object_id',
        ]);

        $classDefinition = $I->haveAPimcoreClass('TestClass');

        $object = $I->haveAPimcoreObject($classDefinition->getName(), 'object-1');

        $I->amOnPageWithLocale(sprintf('http://test-domain3.test/en/news/%d', $object->getId()), 'en');

        $I->seeElement('link', ['href' => sprintf('http://test-domain1.test/en/news/%d', $object->getId()), 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain1.test/en/news/%d', $object->getId()), 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain1.test/de/beitrag/%d', $object->getId()), 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain2.test/it/notizia/%d', $object->getId()), 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain3.test/nouvelles/%d', $object->getId()), 'hreflang' => 'fr']);
    }

    public function staticRouteAlternateInZoneTwoTest(FunctionalTester $I)
    {
        $this->setupSites($I);

        $I->haveAStaticRoute('test_route', [
            'pattern'   => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/',
            'reverse'   => '/{%_locale}/@testKey/%object_id',
            'action'    => 'defaultAction',
            'variables' => '_locale,object_id',
        ]);

        $classDefinition = $I->haveAPimcoreClass('TestClass');

        $object = $I->haveAPimcoreObject($classDefinition->getName(), 'object-1');

        $I->amOnPageWithLocale(sprintf('http://test-domain4.test/en/news/%d', $object->getId()), 'en');

        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/de/beitrag/%d', $object->getId()), 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/en/news/%d', $object->getId()), 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/de/beitrag/%d', $object->getId()), 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/en-us/news/%d', $object->getId()), 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/de-de/artikel/%d', $object->getId()), 'hreflang' => 'de-de']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain4.test/de-ch/artikel/%d', $object->getId()), 'hreflang' => 'de-ch']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain5.test/it/notizia/%d', $object->getId()), 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => sprintf('http://test-domain6.test/nouvelles/%d', $object->getId()), 'hreflang' => 'fr']);
    }
}