<?php

namespace DachcomBundle\Test\FunctionalZoneSimple;

use DachcomBundle\Test\FunctionalTester;

class ZoneCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testZoneRedirectFromDocumentRootWithoutMatchingLanguage(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document = $I->haveAPageDocumentForSite($site1, 'en', 'en');

        $I->amOnPageWithLocale('http://test-domain1.test/', 'de-CH');

        $I->seeCurrentUrlEquals('/en');
        $I->seeCurrentHostEquals('test-domain1.test');
        $I->seePreviousResponseCodeIsRedirection();

        $I->see($document->getId(), '#page-id');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testZoneRedirectFromDocumentSingleWithMatchingLanguage(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document = $I->haveAPageDocumentForSite($site1, 'de', 'de');

        $I->amOnPageWithLocale('http://test-domain1.test/', 'de-CH');

        $I->seeCurrentUrlEquals('/de');
        $I->seeCurrentHostEquals('test-domain1.test');
        $I->seePreviousResponseCodeIsRedirection();

        $I->see($document->getId(), '#page-id');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testZoneRedirectFromDocumentRootWithMultipleLanguages(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'de', 'de');

        $I->amOnPageWithLocale('http://test-domain1.test', 'de-CH');

        $I->seeCurrentUrlEquals('/de');
        $I->seeCurrentHostEquals('test-domain1.test');
        $I->seePreviousResponseCodeIsRedirection();

        $I->see($document2->getId(), '#page-id');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testZoneNonRedirectsWithLocalizedRootSite(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test', 'de');
        $site2 = $I->haveASite('test-domain2.test');

        $document1 = $I->haveAPageDocumentForSite($site2, 'en', 'en');
        $document2 = $I->haveAPageDocumentForSite($site2, 'fr', 'fr');

        $I->amOnPageWithLocale('http://test-domain1.test', 'de-CH');

        $I->seeCurrentUrlEquals('/');
        $I->seeCurrentHostEquals('test-domain1.test');

        $I->see($site1->getRootDocument()->getId(), '#page-id');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testZoneRedirectsWithLocalizedRootSite(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');
        $site2 = $I->haveASite('test-domain2.test', 'de');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'fr', 'fr');

        $I->amOnPageWithLocale('http://test-domain1.test', 'de-CH');

        $I->seeCurrentUrlEquals('/');
        $I->seeCurrentHostEquals('test-domain2.test');

        $I->see($site2->getRootDocument()->getId(), '#page-id');
    }
}