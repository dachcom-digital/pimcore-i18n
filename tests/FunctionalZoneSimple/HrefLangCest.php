<?php

namespace DachcomBundle\Test\FunctionalZoneSimple;

use DachcomBundle\Test\Support\FunctionalTester;

class HrefLangCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testZoneHrefLangTagWithLanguagesAndSingleDomain(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'de', [], 'de');

        $I->amOnPageWithLocale('http://test-domain1.test/', 'de_CH');

        $I->seeCurrentUrlEquals('/de');

        $I->seeElement('html', ['lang' => 'de']);

        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'en']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testZoneHrefLangTagWithLanguagesAndMultipleDomains(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');
        $site2 = $I->haveASite('test-domain2.test');
        $site3 = $I->haveASite('test-domain3.test');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveAPageDocumentForSite($site2, 'de', [], 'de');
        $document3 = $I->haveAPageDocumentForSite($site3, 'fr', [], 'fr');

        $I->amOnPageWithLocale('http://test-domain1.test/', 'de_CH');

        $I->seeCurrentUrlEquals('/de');

        $I->seeElement('html', ['lang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://test-domain2.test/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain3.test/fr', 'hreflang' => 'fr']);
    }
}