<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\Support\FunctionalTester;

class LanguageRequestCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithoutMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en', [], 'en');

        $I->amOnPageWithLocale('/', 'de_CH');
        $I->seeCurrentUrlEquals('/en');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentSingleWithMatchingLanguage(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('de', [], 'de');

        $I->amOnPageWithLocale('/', 'de_CH');
        $I->seeCurrentUrlEquals('/de');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithMultipleLanguages(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAPageDocument('de', [], 'de');

        $I->amOnPageWithLocale('/', 'de_CH');
        $I->seeCurrentUrlEquals('/de');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRedirectFromDocumentRootWithMultipleLanguagesAndMultipleAcceptLocales(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAPageDocument('de', [], 'de');
        $document3 = $I->haveAPageDocument('fr', [], 'fr');

        $I->amOnPageWithLocale('/', ['de_CH', 'en_US', 'fr_FR', 'fr_CH', 'fr']);
        $I->seeCurrentUrlEquals('/fr');
    }
}