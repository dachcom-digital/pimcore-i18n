<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class InternalLinksCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testInternalLink(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $documentSub1 = $I->haveASubPageDocument($document1, 'sub-page-1');
        $documentSubSub1 = $I->haveASubPageDocument($documentSub1, 'sub-sub-page-1');

        $I->haveASubLink($document1, $documentSubSub1, 'sub-link-1');
        $I->haveAHardLink($document1, 'en-us', [], 'en_US');

        $I->amOnPageWithLocaleAndCountry('/en', 'en_US', 'us');
        $I->seeLink('sub-link-1', '/en/sub-page-1/sub-sub-page-1');

        $I->amOnPageWithLocaleAndCountry('/en-us', 'en_US', 'us');
        $I->seeLink('sub-link-1', '/en-us/sub-page-1/sub-sub-page-1');
    }
}