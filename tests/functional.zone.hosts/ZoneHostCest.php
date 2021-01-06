<?php

namespace DachcomBundle\Test\FunctionalZoneHosts;

use DachcomBundle\Test\FunctionalTester;

class ZoneHostCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testZoneWithAdditionalDomains(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain8.test', [], null, true, ['test-domain8.test']);

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveASubPageDocument($document1, 'error', [], 'en');
        $document3 = $I->haveAPageDocumentForSite($site1, 'de', [], 'de');
        $document4 = $I->haveASubPageDocument($document3, 'error', [], 'de');

        $I->amOnPageWithLocale('http://www.test-domain8.test/en/this-is-not-real', 'en');
        $I->seeCurrentUrlEquals('/en/this-is-not-real');
        $I->seeCurrentHostEquals('www.test-domain8.test');
        $I->see($document2->getId(), '#page-id');

        $I->amOnPageWithLocale('http://test-domain8.test/en/this-is-not-real', 'en');
        $I->seeCurrentUrlEquals('/en/this-is-not-real');
        $I->seeCurrentHostEquals('test-domain8.test');
        $I->see($document2->getId(), '#page-id');

        $I->amOnPageWithLocale('http://www.test-domain8.test/de/nicht-existent', 'de');
        $I->seeCurrentUrlEquals('/de/nicht-existent');
        $I->seeCurrentHostEquals('www.test-domain8.test');
        $I->see($document4->getId(), '#page-id');

        $I->amOnPageWithLocale('http://test-domain8.test/de/nicht-existent', 'de');
        $I->seeCurrentUrlEquals('/de/nicht-existent');
        $I->seeCurrentHostEquals('test-domain8.test');
        $I->see($document4->getId(), '#page-id');
    }
}