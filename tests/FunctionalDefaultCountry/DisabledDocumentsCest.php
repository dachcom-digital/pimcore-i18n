<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\Support\FunctionalTester;

class DisabledDocumentsCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testDisabledRootDocument(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAPageDocument('de', [], 'de');
        $document3 = $I->haveAPageDocument('it', [], 'it');

        $I->haveAUnPublishedDocument($document3);

        $I->amOnPageWithLocale('/de', 'de_CH');

        $I->seeCurrentUrlEquals('/de');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'international']);
        $I->seeElement('html', ['lang' => 'de']);

        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'en']);

        $I->dontSeeElement('link', ['href' => 'http://localhost/it', 'hreflang' => 'it']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testDisabledRootHardLink(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAPageDocument('de', [], 'de');

        $hardlink1 = $I->haveAHardLink($document2, 'de-de', [], 'de_DE');
        $hardlink2 = $I->haveAHardLink($document2, 'de-ch', [], 'de_CH');

        $I->haveAUnPublishedDocument($hardlink1);

        $I->amOnPageWithLocale('/de-ch', 'de_CH');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'CH']);
        $I->seeElement('html', ['lang' => 'de_CH']);

        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/de', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://localhost/en', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/de-ch', 'hreflang' => 'de-ch']);

        $I->dontSeeElement('link', ['href' => 'http://localhost/de-de', 'hreflang' => 'de-de']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testDisabledSubDocument(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAPageDocument('de', [], 'de');
        $document3 = $I->haveAPageDocument('it', [], 'it');

        $documentSub1 = $I->haveASubPageDocument($document1, 'about-us', [], 'en');
        $documentSub2 = $I->haveASubPageDocument($document2, 'ueber-uns', [], 'de');
        $documentSub3 = $I->haveASubPageDocument($document3, 'riguardo-a-noi', [], 'it');

        $I->haveTwoConnectedDocuments($documentSub1, $documentSub2);
        $I->haveTwoConnectedDocuments($documentSub1, $documentSub3);

        $I->haveAUnPublishedDocument($documentSub2);

        $I->amOnPageWithLocale('/en/about-us', 'en_US');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'international']);
        $I->seeElement('html', ['lang' => 'en']);

        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://localhost/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://localhost/it/riguardo-a-noi', 'hreflang' => 'it']);

        $I->dontSeeElement('link', ['href' => 'http://localhost/de/ueber-uns', 'hreflang' => 'de']);
    }
}