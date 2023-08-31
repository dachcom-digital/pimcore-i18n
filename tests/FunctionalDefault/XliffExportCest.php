<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\Support\FunctionalTester;

class XliffExportCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testXliffExport(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en', ['action' => 'languageSelectorAction'], 'en');

        $I->haveAUserWithAdminRights('dachcom_test');
        $I->amLoggedInAs('dachcom_test');

        $I->submitDocumentToXliffExporter($document);
    }

}