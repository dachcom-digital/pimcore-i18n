<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class HardLinkWrappingCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testRedirectAHardLinkWrapper(FunctionalTester $I)
    {
        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAHardLink($document1, 'en-us', 'en_US');

        $I->amOnPageWithLocaleAndCountry('/', 'en-US', 'us');

        $I->seeCurrentUrlEquals('/en-us');

        $I->seeElement('meta', ['name' => 'country', 'content' => 'US']);
        $I->seeElement('html', ['lang' => 'en_US']);
    }
}