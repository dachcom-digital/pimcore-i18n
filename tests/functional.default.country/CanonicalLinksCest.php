<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class CanonicalLinksCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testNonCanonicalLinkInRootHardLink(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', [], 'en_US');

        $I->amOnPageWithLocaleAndCountry('/en-us', 'en_US', 'us');

        $I->dontSeeCanonicalLinkInResponse();
    }

    /**
     * @param FunctionalTester $I
     */
    public function testCanonicalLinkInSubHardLink(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $hardlink1 = $I->haveAHardLink($document1, 'en-us', [], 'en_US');

        $document3 = $I->haveASubPageDocument($hardlink1, 'sub-page');
        $document4 = $I->haveASubPageDocument($document3, 'sub-sub-page');

        $hardlink2 = $I->haveASubHardLink($hardlink1, $document3, 'sub-page-hardlink');

        $I->amOnPageWithLocaleAndCountry('/en-us/sub-page-hardlink/sub-sub-page', 'en_US', 'us');

        $I->seeCanonicalLinkInResponse();
    }
}