<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class LocalizedErrorDocumentsCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testDefaultErrorPage(FunctionalTester $I)
    {
        $defaultErrorDocument = $I->haveAPageDocument('error', 'en');

        $document1 = $I->haveAPageDocument('en', 'en');
        $document2 = $I->haveAPageDocument('de', 'de');

        $I->amOnPageWithLocaleAndCountry('/this-page-does-not-exist', 'de-CH', 'switzerland');

        $I->seeCurrentUrlEquals('/this-page-does-not-exist');

        $I->see($defaultErrorDocument->getId(), '#page-id');

    }

    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedCountryErrorPage(FunctionalTester $I)
    {
        // we need to unpublish the default page here
        // since the default error page is placed on root level (defined in system.php)
        $defaultErrorDocument = $I->haveAPageDocument('error', 'en');
        $I->haveAUnPublishedDocument($defaultErrorDocument);

        $document1 = $I->haveAPageDocument('en', 'en');
        $localizedErrorDocument1 = $I->haveASubPageDocument($document1,'error');

        $document2 = $I->haveAPageDocument('de', 'de');
        $localizedErrorDocument2 = $I->haveASubPageDocument($document2,'error');

        $I->amOnPageWithLocaleAndCountry('/en/this-page-does-not-exist', 'en-US', 'us');
        $I->see($localizedErrorDocument1->getId(), '#page-id');

        $I->amOnPageWithLocaleAndCountry('/de/diese-seite-existiert-nicht', 'de-DE', 'germany');
        $I->see($localizedErrorDocument2->getId(), '#page-id');

    }
}