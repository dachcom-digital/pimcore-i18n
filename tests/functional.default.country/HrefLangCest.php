<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class HrefLangCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithLanguages(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAPageDocument('de', 'de');

        $I->amOnPageWithLocale('/', 'de-CH');

        $I->seeCurrentUrlEquals('/en');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'international']);
        $I->seeElement('html', ['lang' => 'en']);

        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'en']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesWithoutXDefault(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('de', 'de');
        $document2 = $I->haveAPageDocument('de-de', 'de_DE');
        $document3 = $I->haveAPageDocument('de-ch', 'de_CH');

        $I->amOnPageWithLocaleAndCountry('/', 'de-CH', 'switzerland');

        $I->seeCurrentUrlEquals('/de-ch');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'CH']);
        $I->seeElement('html', ['lang' => 'de_CH']);

        $I->dontSeeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/de-ch', 'hreflang' => 'de-ch']);
        $I->seeElement('link', ['href' => 'http://localhost/de-de', 'hreflang' => 'de-de']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesWithXDefault(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAPageDocument('de', 'de');
        $document3 = $I->haveAPageDocument('en-us', 'en_US');

        $I->amOnPageWithLocaleAndCountry('/', 'en-US', 'us');

        $I->seeCurrentUrlEquals('/en-us');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'US']);
        $I->seeElement('html', ['lang' => 'en_US']);

        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/en-us', 'hreflang' => 'en-us']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesAndHardlinks(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $document2 = $I->haveAPageDocument('de', 'de');
        $hardlink2 = $I->haveAHardLink($document1, 'de-de', 'de_DE');

        $I->amOnPageWithLocaleAndCountry('/', 'en-US', 'us');

        $I->seeCurrentUrlEquals('/en-us');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'US']);
        $I->seeElement('html', ['lang' => 'en_US']);

        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/en-us', 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => 'http://localhost/de-de', 'hreflang' => 'de-de']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesAndHardlinksAndDynamicSubDocuments(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $document2 = $I->haveAPageDocument('de', 'de');
        $hardlink2 = $I->haveAHardLink($document1, 'de-de', 'de_DE');

        $documentSub1 = $I->haveASubPageDocument($document1, 'about-us');
        $documentSub2 = $I->haveASubPageDocument($document2, 'ueber-uns');

        $I->haveTwoConnectedDocuments($documentSub1, $documentSub2);

        $I->amOnPageWithLocaleAndCountry('/en/about-us', 'en-US', 'us');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'international']);
        $I->seeElement('html', ['lang' => 'en']);

        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de/ueber-uns', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/en-us/about-us', 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => 'http://localhost/de-de/ueber-uns', 'hreflang' => 'de-de']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesAndHardlinksAndCustomDocumentsWithoutConnection(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $document2 = $I->haveAPageDocument('de', 'de');
        $hardlink2 = $I->haveAHardLink($document1, 'de-de', 'de_DE');

        $documentSub1 = $I->haveASubPageDocument($document1, 'about-us');
        $documentSub2 = $I->haveASubPageDocument($document2, 'ueber-uns');
        $documentSub3 = $I->haveASubPageDocument($hardlink2, 'ueber-uns');

        $I->haveTwoConnectedDocuments($documentSub1, $documentSub2);

        $I->amOnPageWithLocaleAndCountry('/en-us/about-us', 'en-US', 'us');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'US']);
        $I->seeElement('html', ['lang' => 'en_US']);

        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de/ueber-uns', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/en-us/about-us', 'hreflang' => 'en-us']);
        $I->dontSeeElement('link', ['href' => 'http://localhost/de-de/ueber-uns', 'hreflang' => 'de-de']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesAndHardlinksAndCustomDocumentsWithConnection(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $document2 = $I->haveAPageDocument('de', 'de');
        $hardlink2 = $I->haveAHardLink($document1, 'de-de', 'de_DE');

        $documentSub1 = $I->haveASubPageDocument($document1, 'about-us');
        $documentSub2 = $I->haveASubPageDocument($document2, 'ueber-uns');
        $documentSub3 = $I->haveASubPageDocument($hardlink2, 'ueber-uns');

        $I->haveTwoConnectedDocuments($documentSub1, $documentSub2);
        $I->haveTwoConnectedDocuments($documentSub1, $documentSub3);

        $I->amOnPageWithLocaleAndCountry('/de-de/ueber-uns', 'de-DE', 'germany');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'DE']);
        $I->seeElement('html', ['lang' => 'de_DE']);

        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de/ueber-uns', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/en-us/about-us', 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => 'http://localhost/de-de/ueber-uns', 'hreflang' => 'de-de']);

        $I->see($documentSub3->getId(), '#page-id');
    }


    /**
     * @param FunctionalTester $I
     */
    public function testHrefLangTagWithCountriesAndHardlinksAndCustomDocumentsWithConnectionButDisabled(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $document2 = $I->haveAPageDocument('de', 'de');
        $hardlink2 = $I->haveAHardLink($document1, 'de-de', 'de_DE');

        $documentSub1 = $I->haveASubPageDocument($document1, 'about-us');
        $documentSub2 = $I->haveASubPageDocument($document2, 'ueber-uns');
        $documentSub3 = $I->haveASubPageDocument($hardlink2, 'ueber-uns');

        $I->haveTwoConnectedDocuments($documentSub1, $documentSub2);
        $I->haveTwoConnectedDocuments($documentSub1, $documentSub3);

        $I->haveAUnPublishedDocument($documentSub3);

        $I->amOnPageWithLocaleAndCountry('/de-de/ueber-uns', 'de-DE', 'germany');
        $I->canSeePageNotFound();
    }
}