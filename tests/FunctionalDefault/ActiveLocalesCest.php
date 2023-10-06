<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\Support\FunctionalTester;

class ActiveLocalesCest
{
    public function testActiveLanguagesSelector(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site, 'de', ['action' => 'languageSelectorAction'], 'de');
        $document2 = $I->haveAPageDocumentForSite($site, 'en', ['action' => 'languageSelectorAction'], 'en');

        $I->haveTwoConnectedDocuments($document1, $document2);

        $I->amOnPageWithLocale('http://test-domain1.test/en', 'en');

        $I->seeElement('select option[selected][value="http://test-domain1.test/en"]');
    }

    public function testActiveCountriesSelector(FunctionalTester $I): void
    {
        $site = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site, 'de', ['action' => 'languageCountrySelectorAction'], 'de');
        $document2 = $I->haveAPageDocumentForSite($site, 'en', ['action' => 'languageCountrySelectorAction'], 'en');

        $I->haveTwoConnectedDocuments($document1, $document2);

        $I->amOnPageWithLocale('http://test-domain1.test/en', 'en');

        $I->seeElement('li[data-country="GLOBAL"] li[data-language="en"]');
    }
}
