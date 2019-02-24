<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;

class StaticRouteKeyTranslationCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedStaticRoute(FunctionalTester $I)
    {
        $staticRoute = $I->haveAStaticRoute('test_route', 'testKey');

        $I->amOnStaticRoute($staticRoute->getName(), ['_locale' => 'en', 'testProperty' => 'universe']);
        $I->seeCurrentUrlEquals('/en/news/universe');

        $I->amOnStaticRoute($staticRoute->getName(), ['_locale' => 'it', 'testProperty' => 'universe']);
        $I->seeCurrentUrlEquals('/it/notizia/universe');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedStaticRouteWithNotAvailableLocale(FunctionalTester $I)
    {
        $staticRoute = $I->haveAStaticRoute('test_route', 'testKey');

        $exception = 'Exception';
        $exceptionMessage = 'i18n static route translation error: ';
        $exceptionMessage .= 'no valid translation key for "testKey" in locale "en_GB" found. ';
        $exceptionMessage .= 'please add it to your i18n translation config';

        $I->seeException($exception, $exceptionMessage, function () use ($I, $staticRoute) {
            $I->amOnStaticRoute($staticRoute->getName(), ['_locale' => 'en_GB', 'testProperty' => 'universe']);
        });
    }
}