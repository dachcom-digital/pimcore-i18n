<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\Support\FunctionalI18nTester;

class FrontpageMappingCest
{
    public function testFrontPageMappingWithRedirecting(FunctionalI18nTester $I): void
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', [], 'en_US');
        $document3 = $I->haveAFrontPageMappedDocument($document2);

        $I->amOnPageWithLocaleAndCountry('/', 'en_US', 'us');
        $I->seeCurrentUrlEquals('/en-us');
        $I->see($document3->getId(), '#page-id');
    }

    public function testFrontPageMappingWithoutRedirecting(FunctionalI18nTester $I): void
    {
        $document1 = $I->haveAPageDocument('en', [], 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', [], 'en_US');
        $document3 = $I->haveAFrontPageMappedDocument($document2);

        $I->amOnPage('/en-us');
        $I->see($document3->getId(), '#page-id');
    }
}
