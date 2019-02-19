<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class CountryRequestCest
{
     /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithoutMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en', 'en');

        $I->amOnPageWithLocaleAndCountry('/', 'de-CH', 'switzerland');

        $I->seeCurrentUrlEquals('/en');
        $I->seePreviousResponseCodeIsRedirection();
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentSingleWithMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('de', 'de');

        $I->amOnPageWithLocaleAndCountry('/', 'de-CH', 'switzerland');

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

        $I->amOnPageWithLocaleAndCountry('/', 'de-CH', 'switzerland');

        $I->seeCurrentUrlEquals('/de');
        $I->seePreviousResponseCodeIsRedirection();
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithoutMatchingLanguageAndCountry(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAPageDocument('en-us', 'en_US');

        $I->amOnPageWithLocaleAndCountry('/', 'de-CH', 'switzerland');

        $I->seeCurrentUrlEquals('/en');
        $I->seePreviousResponseCodeIsRedirection();
    }


    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentSingleWithMatchingLanguageAndCountry(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en-us', 'en_US');

        $I->amOnPageWithLocaleAndCountry('/', 'en-US', 'us');

        $I->seeCurrentUrlEquals('/en-us');
        $I->seePreviousResponseCodeIsRedirection();
    }


    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithMultipleLanguagesAndCountries(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en-us', 'en_US');
        $document2 = $I->haveAPageDocument('de-de', 'de_DE');

        $I->amOnPageWithLocaleAndCountry('/', 'de-DE', 'germany');

        $I->seeCurrentUrlEquals('/de-de');
        $I->seePreviousResponseCodeIsRedirection();
    }

}