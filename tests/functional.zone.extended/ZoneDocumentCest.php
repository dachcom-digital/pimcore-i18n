<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\FunctionalTester;

class ZoneDocumentCest extends AbstractZone
{
    public function zoneOneWithSubDocumentsTest(FunctionalTester $I)
    {
        $data = $this->setupSites($I);

        $document1 = $I->haveASubPageDocument($data['document1'], 'about-us', [], 'en');
        $document2 = $I->haveASubPageDocument($data['document2'], 'ueber-uns', [], 'de');
        $document3 = $I->haveASubPageDocument($data['document3'], 'riguardo-a-noi', [], 'it');
        $document4 = $I->haveASubPageDocument($data['site3']->getRootDocument(), 'propos-de-nous', [], 'fr');

        $I->haveTwoConnectedDocuments($document1, $document2);
        $I->haveTwoConnectedDocuments($document1, $document3);
        $I->haveTwoConnectedDocuments($document1, $document4);

        $I->amOnPageWithLocale('http://test-domain1.test/de/ueber-uns', 'de_CH');

        $I->seeElement('html', ['lang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en/about-us', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://test-domain1.test/de/ueber-uns', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain2.test/it/riguardo-a-noi', 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => 'http://test-domain3.test/propos-de-nous', 'hreflang' => 'fr']);
    }

    public function zoneTwoWithSubDocumentsTest(FunctionalTester $I)
    {
        $data = $this->setupSites($I);

        $document1 = $I->haveASubPageDocument($data['document4'], 'about-us', [], 'en');
        $document2 = $I->haveASubPageDocument($data['document5'], 'ueber-uns', [], 'de');
        $document3 = $I->haveASubPageDocument($data['site6']->getRootDocument(), 'propos-de-nous', [], 'fr');
        $document4 = $I->haveASubPageDocument($data['document6'], 'riguardo-a-noi', [], 'it');

        $I->haveTwoConnectedDocuments($document1, $document2);
        $I->haveTwoConnectedDocuments($document1, $document3);
        $I->haveTwoConnectedDocuments($document1, $document4);

        $I->amOnPageWithLocale('http://test-domain4.test/de/ueber-uns', 'de_CH');

        $I->seeElement('html', ['lang' => 'de']);
        // default language => "de" (define in zone config)
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de/ueber-uns', 'hreflang' => 'x-default']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/en/about-us', 'hreflang' => 'en']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/en-us/about-us', 'hreflang' => 'en-us']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de/ueber-uns', 'hreflang' => 'de']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de-de/ueber-uns', 'hreflang' => 'de-de']);
        $I->seeElement('link', ['href' => 'http://test-domain4.test/de-ch/ueber-uns', 'hreflang' => 'de-ch']);
        $I->seeElement('link', ['href' => 'http://test-domain5.test/it/riguardo-a-noi', 'hreflang' => 'it']);
        $I->seeElement('link', ['href' => 'http://test-domain6.test/propos-de-nous', 'hreflang' => 'fr']);
    }

    public function zoneThreeWithSubDocumentsTest(FunctionalTester $I)
    {
        $data = $this->setupSites($I);
        $document1 = $I->haveASubPageDocument($data['site7']->getRootDocument(), 'riguardo-a-nois', [], 'it');

        $I->amOnPageWithLocale('http://test-domain7.test/riguardo-a-nois', 'it');
        $I->seeElement('html', ['lang' => 'it']);
    }
}