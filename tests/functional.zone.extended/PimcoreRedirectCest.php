<?php

namespace DachcomBundle\Test\FunctionalZoneExtend;

use DachcomBundle\Test\FunctionalTester;

class PimcoreRedirectCest extends AbstractZone
{
    /**
     * @param FunctionalTester $I
     */
    public function testExtendedLocalizedRedirectPath(FunctionalTester $I)
    {
        $data = $this->setupSites($I);

        $document1 = $I->haveASubPageDocument($data['document1'], 'about-us', [], 'en');
        $document2 = $I->haveASubPageDocument($data['document2'], 'ueber-uns', [], 'de');
        $document3 = $I->haveASubPageDocument($data['document3'], 'riguardo-a-noi', [], 'it');
        $document4 = $I->haveASubPageDocument($data['site3']->getRootDocument(), 'propos-de-nous', [], 'fr');

        $I->haveTwoConnectedDocuments($document1, $document2);
        $I->haveTwoConnectedDocuments($document1, $document3);
        $I->haveTwoConnectedDocuments($document1, $document4);

        $redirect = [
            'type'       => 'path',
            'source'     => '@^/my-special-redirect@',
            'sourceSite' => $data['site1']->getId(),
            'target'     => sprintf('/{i18n_localized_target_page=%s}', $document3->getId()),
            'targetSite' => $data['site1']->getId(),
            'statusCode' => 301,
            'regex'      => 1,
        ];

        $I->haveAPimcoreRedirect($redirect);
        $I->amOnPageWithLocale('http://test-domain1.test/my-special-redirect', 'it');
        $I->seeCurrentUrlEquals('/it/riguardo-a-noi');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testExtendedLocalizedRedirectPathNotFound(FunctionalTester $I)
    {
        $data = $this->setupSites($I);

        $document1 = $I->haveASubPageDocument($data['document1'], 'about-us', [], 'en');
        $document2 = $I->haveASubPageDocument($data['document2'], 'ueber-uns', [], 'de');
        $document3 = $I->haveASubPageDocument($data['document3'], 'riguardo-a-noi', [], 'it');
        $document4 = $I->haveASubPageDocument($data['site3']->getRootDocument(), 'propos-de-nous', [], 'fr');

        $I->haveTwoConnectedDocuments($document1, $document2);
        $I->haveTwoConnectedDocuments($document1, $document3);
        $I->haveTwoConnectedDocuments($document1, $document4);

        $redirect = [
            'type'       => 'path',
            'source'     => '@^/my-special-redirect@',
            'sourceSite' => null,
            'target'     => sprintf('/{i18n_localized_target_page=%s}', $document3->getId()),
            'targetSite' => null,
            'statusCode' => 301,
            'regex'      => 1,
        ];

        $I->haveAPimcoreRedirect($redirect);
        $I->amOnPageWithLocale('http://test-domain1.test/my-special-redirect', 'it');
        $I->seeCurrentUrlEquals('/my-special-redirect');
        $I->canSeePageNotFound();
    }
}