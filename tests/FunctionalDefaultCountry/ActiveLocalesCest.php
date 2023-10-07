<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\Support\FunctionalTester;

class ActiveLocalesCest
{
    public function testActiveLanguagesSelector(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site, 'de-de', ['action' => 'languageSelectorAction'], 'de_DE');
        $document2 = $I->haveAPageDocumentForSite($site, 'de-ch', ['action' => 'languageSelectorAction'], 'de_CH');

        $I->haveTwoConnectedDocuments($document1, $document2);

        $I->amOnPageWithLocale('http://test-domain1.test/de-de', 'de');

        $I->seeElement('select option[selected][value="http://test-domain1.test/de-de"]');
    }

    public function testActiveCountriesSelector(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site, 'de-de', ['action' => 'languageCountrySelectorAction'], 'de_DE');
        $document2 = $I->haveAPageDocumentForSite($site, 'de-ch', ['action' => 'languageCountrySelectorAction'], 'de_CH');

        $I->haveTwoConnectedDocuments($document1, $document2);

        $I->amOnPageWithLocale('http://test-domain1.test/de-ch', 'de');

        $I->seeElement('li[data-country="CH"] li[data-language="de"]');
    }
}
