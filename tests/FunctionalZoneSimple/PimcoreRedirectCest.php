<?php

namespace DachcomBundle\Test\FunctionalZoneSimple;

use DachcomBundle\Test\Support\FunctionalTester;

class PimcoreRedirectCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedRedirectPath(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'de', [], 'de');

        $redirect = [
            'type'       => 'path',
            'source'     => '@^/my-special-redirect-uri@',
            'sourceSite' => $site1->getId(),
            'target'     => sprintf('/{i18n_localized_target_page=%s}', $document2->getId()),
            'targetSite' => null,
            'statusCode' => 301,
            'regex'      => 1,
        ];

        $I->haveAPimcoreRedirect($redirect);
        $I->amOnPageWithLocale('http://test-domain1.test/my-special-redirect-uri', 'de');
        $I->seeCurrentUrlEquals('/de');

    }

    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedRedirectEntireUri(FunctionalTester $I)
    {
        $site1 = $I->haveASite('test-domain1.test');

        $document1 = $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $document2 = $I->haveAPageDocumentForSite($site1, 'de', [], 'de');

        $redirect = [
            'type'       => 'entire_uri',
            'source'     => '@http://test-domain3\.test@',
            'sourceSite' => null,
            'target'     => sprintf('/{i18n_localized_target_page=%s}', $document2->getId()),
            'targetSite' => null,
            'statusCode' => 301,
            'regex'      => 1,
            'priority'   => 99
        ];

        $I->haveAPimcoreRedirect($redirect);
        $I->amOnPageWithLocale('http://test-domain3.test', 'de');

        $I->seeCurrentHostEquals('test-domain1.test');
        $I->seeCurrentUrlEquals('/de');

    }
}