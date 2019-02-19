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
        $I->seePreviousResponseCodeIsRedirection();

        $I->see($document2->getId(), '#page-id');
    }
}