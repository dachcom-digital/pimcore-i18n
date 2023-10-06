<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\Support\FunctionalTester;

class CountryRedirectorCest
{
    public function testCanonicalLinkInSubHardLink(FunctionalTester $I): void
    {
        $document1 = $I->haveAPageDocument('de-de', [], 'de_DE');
        $document2 = $I->haveAPageDocument('de-ch', [], 'de_CH');

        $I->amOnPageWithLocaleAndCountry('/', 'en', 'switzerland');

        $I->seeCurrentUrlEquals('/de-ch');
    }
}
