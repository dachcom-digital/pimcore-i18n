<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\FunctionalTester;

class XliffExportCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testXliffExport(FunctionalTester $I)
    {
        $document = $I->haveAPageDocument('en', 'en');

        $user = $I->haveAUser('dachcom_test');
        $I->amLoggedInAs('dachcom_test');

        $I->submitDocumentToXliffExporter($document);
    }

}