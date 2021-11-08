<?php

namespace DachcomBundle\Test\FunctionalFullPageCache;

use DachcomBundle\Test\FunctionalTester;

class FullPageCacheCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testFullPageCacheEnabled(FunctionalTester $I)
    {
        $I->haveAKernelWithoutDebugMode();

        $site1 = $I->haveASite('test-domain1.test');
        $I->haveAPageDocumentForSite($site1, 'en', [], 'en');
        $I->haveAPageDocumentForSite($site1, 'de', [], 'de');

        $I->amOnPageWithLocale('http://test-domain1.test/de', 'de');

        $I->dontSeePimcoreOutputCacheDisabledHeader();
        $I->seePimcoreOutputCacheDate();
        $I->seeEmptySessionBag('i18n_session');
    }
}