<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\Support\FunctionalTester;

abstract class AbstractZone
{
    protected function setupSites(FunctionalTester $I, bool $addErrorDocuments = false)
    {
        // zone 1 (language)
        $site1 = $I->haveASite('test-domain1.test', [], null, false, [], $addErrorDocuments ? [
            'en' => '/test-domain1-test/en/error',
            'de' => '/test-domain1-test/de/error',
            'it' => '/test-domain1-test/it/error',
        ] : []);

        $site2 = $I->haveASite('test-domain2.test', [], null, false, [], $addErrorDocuments ? [
            'it' => '/test-domain2-test/it/error',
        ] : []);

        $site3 = $I->haveASite('test-domain3.test', [], 'fr', false, [], $addErrorDocuments ? [
            'default' => '/test-domain3-test/error',
            'fr'      => '/test-domain3-test/error',
        ] : []);

        // zone 2 (country)
        $site4 = $I->haveASite('test-domain4.test', [], null, false, [], $addErrorDocuments ? [
            'en'    => '/test-domain4-test/en/error',
            'de'    => '/test-domain4-test/de/error',
            'en_US' => '/test-domain4-test/en-us/error',
            'de_DE' => '/test-domain4-test/de-de/error',
            'de_CH' => '/test-domain4-test/de-ch/error',
        ] : []);

        $site5 = $I->haveASite('test-domain5.test', [], null, false, [], $addErrorDocuments ? [
               'it'    => '/test-domain5-test/it/error',
        ] : []);

        $site6 = $I->haveASite('test-domain6.test', [], 'fr', false, [], $addErrorDocuments ? [
            'default' => '/test-domain6-test/error',
            'fr'      => '/test-domain6-test/error',
        ] : []);

        // zone 3 (language)
        $site7 = $I->haveASite('test-domain7.test', [], 'it', false, [], $addErrorDocuments ? [
            'default' => '/test-domain7-test/error',
            'it'      => '/test-domain7-test/error',
        ] : []);

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'de', [], 'de');
        $document3 = $I->haveAPageDocumentForSite($site2, 'it', [], 'it');

        $document4 = $I->haveAPageDocumentForSite($site4, 'en', [], 'en');
        $document5 = $I->haveAPageDocumentForSite($site4, 'de', [], 'de');

        $hardlink1 = $I->haveAHardlinkForSite($site4, $document4, 'en-us', [], 'en_US');
        $hardlink2 = $I->haveAHardlinkForSite($site4, $document5, 'de-de', [], 'de_DE');
        $hardlink3 = $I->haveAHardlinkForSite($site4, $document5, 'de-ch', [], 'de_CH');

        $document6 = $I->haveAPageDocumentForSite($site5, 'it', [], 'it');

        return [
            'site1'     => $site1,
            'site2'     => $site2,
            'site3'     => $site3,
            'site4'     => $site4,
            'site5'     => $site5,
            'site6'     => $site6,
            'site7'     => $site7,
            'document1' => $document1,
            'document2' => $document2,
            'document3' => $document3,
            'document4' => $document4,
            'document5' => $document5,
            'document6' => $document6,
            'hardlink1' => $hardlink1,
            'hardlink2' => $hardlink2,
            'hardlink3' => $hardlink3,
        ];
    }
}