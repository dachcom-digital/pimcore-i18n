<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\FunctionalTester;

class ZoneCest extends AbstractZone
{
    public function zoneOneTest(FunctionalTester $I)
    {
        $this->setupSites($I);
        $I->amOnPageWithLocale('http://test-domain1.test/', 'fr_FR');

        $I->seeCurrentUrlEquals('/');
        $I->seeCurrentHostEquals('test-domain3.test');

        $I->seeElement('html', ['lang' => 'fr']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain2.test/it', 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => 'http://test-domain3.test', 'hreflang' => 'fr']);
    }

    public function zoneTwoTest(FunctionalTester $I)
    {
        $this->setupSites($I);
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/', 'de_CH', 'switzerland');

        $I->seeCurrentUrlEquals('/de-ch');
        $I->seeCurrentHostEquals('test-domain4.test');

        $I->seeElement('html', ['lang' => 'de_CH']);
        $I->seeElement('meta', ['name' => 'country', 'content' => 'CH']);
        // default language => "de" (define in zone config)
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/en-us', 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de-de', 'hreflang' => 'de-de']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de-ch', 'hreflang' => 'de-ch']);
        $I->seeElement('link', ['href' => 'http://test-domain5.test/it', 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => 'http://test-domain6.test', 'hreflang' => 'fr']);
    }

    public function zoneThreeTest(FunctionalTester $I)
    {
        $this->setupSites($I);
        $I->amOnPageWithLocaleAndCountry('http://test-domain7.test/', 'de_CH', 'switzerland');

        $I->seeCurrentUrlEquals('/');
        $I->seeCurrentHostEquals('test-domain7.test');

        $I->seeElement('html', ['lang' => 'it']);
        // default language => "de" (define in zone config)
        $I->seeElement('link', ['href' => 'http://test-domain7.test', 'hreflang' => 'it']);
    }
}