<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\FunctionalTester;

class LanguageRequestCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithoutMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en', 'en');

        $I->amOnPageWithLocale('/', 'de-CH');

        $I->seeCurrentUrlEquals('/en');
        $I->seePreviousResponseCodeIsRedirection();
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentSingleWithMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('de', 'de');

        $I->amOnPageWithLocale('/', 'de-CH');

        $I->seeCurrentUrlEquals('/de');
        $I->seePreviousResponseCodeIsRedirection();
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithMultipleLanguages(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAPageDocument('de', 'de');

        $I->amOnPageWithLocale('/', 'de-CH');

        $I->seeCurrentUrlEquals('/de');
        $I->seePreviousResponseCodeIsRedirection();

    }
}