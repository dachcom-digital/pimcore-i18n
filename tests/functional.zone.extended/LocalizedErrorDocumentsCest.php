<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\FunctionalTester;

class LocalizedErrorDocumentsCest extends AbstractZone
{
    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedErrorPageInDifferentZones(FunctionalTester $I)
    {
        $setup = $this->setupSites($I);

        // we need to unpublish the default page here
        // since the default error page is placed on root level (defined in system.php)
        $defaultErrorDocument = $I->haveAPageDocument('error', 'en');
        $I->haveAUnPublishedDocument($defaultErrorDocument);

        // zone 1
        $localizedErrorDocument1 = $I->haveASubPageDocument($setup['document1'], 'error');
        $localizedErrorDocument2 = $I->haveASubPageDocument($setup['document2'], 'error');
        $localizedErrorDocument3 = $I->haveASubPageDocument($setup['document3'], 'error');
        $localizedErrorDocument4 = $I->haveAPageDocumentForSite($setup['site3'], 'error');

        // zone 2
        $localizedErrorDocument5 = $I->haveASubPageDocument($setup['document4'], 'error');
        $localizedErrorDocument6 = $I->haveASubPageDocument($setup['document5'], 'error');
        $localizedErrorDocument7 = $I->haveASubPageDocument($setup['hardlink1'], 'error');
        $localizedErrorDocument8 = $I->haveASubPageDocument($setup['hardlink2'], 'error');
        $localizedErrorDocument9 = $I->haveASubPageDocument($setup['hardlink3'], 'error');
        $localizedErrorDocument10 = $I->haveASubPageDocument($setup['document6'], 'error');
        $localizedErrorDocument11 = $I->haveAPageDocumentForSite($setup['site6'], 'error');

        // zone 3
        $localizedErrorDocument12 = $I->haveAPageDocumentForSite($setup['site7'], 'error');

        // zone 1 requests
        $I->amOnPageWithLocaleAndCountry('http://test-domain1.test/en/this-page-does-not-exist', 'de-DE', 'germany');
        $I->see($localizedErrorDocument1->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain1.test/de/diese-seite-existiert-nicht', 'de-DE', 'germany');
        $I->see($localizedErrorDocument2->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain2.test/it/questa-pagina-non-esiste', 'de-DE', 'germany');
        $I->see($localizedErrorDocument3->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain3.test/cette-page-nexiste-pas', 'de-DE', 'germany');
        $I->see($localizedErrorDocument4->getId(), '#page-id');

        // zone 2 requests
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/en/this-page-does-not-exist', 'de-DE', 'germany');
        $I->see($localizedErrorDocument5->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/de/diese-seite-existiert-nicht', 'de-DE', 'germany');
        $I->see($localizedErrorDocument6->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/en-us/this-page-does-not-exist', 'de-DE', 'germany');
        $I->see($localizedErrorDocument7->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/de-de/diese-seite-existiert-nicht', 'de-DE', 'germany');
        $I->see($localizedErrorDocument8->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain4.test/de-ch/diese-seite-existiert-nicht', 'de-DE', 'germany');
        $I->see($localizedErrorDocument9->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain5.test/it/questa-pagina-non-esiste', 'de-DE', 'germany');
        $I->see($localizedErrorDocument10->getId(), '#page-id');
        $I->amOnPageWithLocaleAndCountry('http://test-domain6.test/cette-page-nexiste-pas', 'de-DE', 'germany');
        $I->see($localizedErrorDocument11->getId(), '#page-id');

        // zone 3 requests
        $I->amOnPageWithLocaleAndCountry('http://test-domain7.test/questa-pagina-non-esiste', 'de-DE', 'germany');
        $I->see($localizedErrorDocument12->getId(), '#page-id');

    }
}