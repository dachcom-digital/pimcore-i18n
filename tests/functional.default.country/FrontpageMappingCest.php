<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class FrontpageMappingCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testFrontPageMappingWithRedirecting(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', 'en_US');
        $document3 = $I->haveAFrontPageMappedDocument($document2);

        $I->amOnPageWithLocaleAndCountry('/', 'en-US', 'us');

        $I->seeCurrentUrlEquals('/en-us');

        $I->see($document3->getId(), '#page-id');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testFrontPageMappingWithoutRedirecting(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', 'en_US');
        $document3 = $I->haveAFrontPageMappedDocument($document2);

        $I->amOnPage('/en-us');
        $I->see($document3->getId(), '#page-id');
    }
}