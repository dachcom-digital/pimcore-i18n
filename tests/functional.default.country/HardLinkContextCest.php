<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class HardLinkContextCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testFrontPathLinkInLanguageWithHardLinkContext(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $documentSub1 = $I->haveASubPageDocument($document1, 'sub-page-1');
        $documentSubSub1 = $I->haveASubPageDocument($documentSub1, 'sub-sub-page-1');

        $I->haveASubLink($document1, $documentSubSub1, 'sub-link-1');
        $I->haveAHardLink($document1, 'en-us', [], 'en_US');

        $I->amOnPageWithLocaleAndCountry('/en-us', 'en_US', 'us');
        $I->seeLink('sub-link-1', '/en-us/sub-page-1/sub-sub-page-1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testFrontPathLinkInCountryWithHardLinkContext(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('de', [], 'de_DE');
        $documentSub1 = $I->haveASubPageDocument($document1, 'sub-page-1');
        $documentSubSub1 = $I->haveASubPageDocument($documentSub1, 'sub-sub-page-1');

        $I->haveASubLink($document1, $documentSubSub1, 'sub-link-1');
        $I->haveAHardLink($document1, 'de-ch', [], 'de_CH');

        $I->amOnPageWithLocaleAndCountry('/de-ch', 'de_CH', 'switzerland');
        $I->seeLink('sub-link-1', '/de-ch/sub-page-1/sub-sub-page-1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testFrontPathLinkInCountryWithoutHardLinkContext(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en_US');
        $documentSub1 = $I->haveASubPageDocument($document1, 'sub-page-1');
        $documentSubSub1 = $I->haveASubPageDocument($documentSub1, 'sub-sub-page-1');

        $I->haveASubLink($document1, $documentSubSub1, 'sub-link-1');

        $I->amOnPageWithLocaleAndCountry('/en', 'en_US', 'us');
        $I->seeLink('sub-link-1', '/en/sub-page-1/sub-sub-page-1');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testLinkRedirectInHardLinkContext(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('de', [], 'de');
        $documentSub1 = $I->haveASubPageDocument($document1, 'sub-page-1');
        $documentSubSub1 = $I->haveASubPageDocument($documentSub1, 'sub-sub-page-1');

        $I->haveASubLink($document1, $documentSubSub1, 'sub-link-1');
        $I->haveAHardLink($document1, 'de-ch', [], 'de_CH');

        $I->amOnPage('/de-ch/sub-link-1');
        $I->seeCurrentUrlEquals('/de-ch/sub-page-1/sub-sub-page-1');
    }
}