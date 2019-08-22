<?php

namespace DachcomBundle\Test\FunctionalDefault;

use DachcomBundle\Test\FunctionalTester;

class FullPageCacheCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testFullPageCacheEnabled(FunctionalTester $I)
    {

        $site1 = $I->haveASite('test-domain1.test');
        $I->haveAPageDocumentForSite($site1, 'en', 'en');
        $I->haveAPageDocumentForSite($site1, 'de', 'de');

        $I->haveFullPageCacheEnabled();

        //$I->amOnPageWithLocale('http://test-domain1.test/en', 'en');

        $I->amOnPageWithLocale('http://test-domain1.test/de', 'de');

        $I->dontSeePimcoreOutputCacheDisabledHeader();
        $I->seePimcoreOutputCacheIsEnabled();
        $I->seeEmptyI18nSessionBag();
    }

}