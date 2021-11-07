<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\FunctionalTester;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class StaticRouteKeyTranslationCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedStaticRoute(FunctionalTester $I)
    {
        $I->haveAPageDocument('en', [], 'en');
        $I->haveAPageDocument('it', [], 'it');

        $staticRoute = $I->haveAStaticRoute('test_route', [
            'pattern'   => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/',
            'reverse'   => '/{%_locale}/@testKey/%testProperty',
            'action'    => 'defaultAction',
            'variables' => '_locale,entry',
        ]);

        $I->amOnStaticRoute($staticRoute->getName(), [
            '_i18n' => [
                'type' => RouteItemInterface::STATIC_ROUTE,
                'routeParameters' => [
                    '_locale' => 'en',
                    'testProperty' => 'universe'
                ]
            ]
        ]);

        $I->seeCurrentUrlEquals('/en/news/universe');

        $I->amOnStaticRoute($staticRoute->getName(), [
            '_i18n' => [
                'type' => RouteItemInterface::STATIC_ROUTE,
                'routeParameters' => [
                    '_locale' => 'it',
                    'testProperty' => 'universe'
                ]
            ]
        ]);
        $I->seeCurrentUrlEquals('/it/notizia/universe');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testLocalizedStaticRouteWithNotAvailableLocale(FunctionalTester $I)
    {
        $I->haveAPageDocument('en', [], 'en');

        $staticRoute = $I->haveAStaticRoute('test_route', [
            'pattern'   => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/',
            'reverse'   => '/{%_locale}/@testKey/%testProperty',
            'action'    => 'defaultAction',
            'variables' => '_locale,entry',
        ]);

        $exception = 'Exception';
        $exceptionMessage = 'I18n: no valid zone site for locale "en_GB" found.';

        $I->seeException($exception, $exceptionMessage, function () use ($I, $staticRoute) {
            $I->amOnStaticRoute($staticRoute->getName(), [
                '_i18n' => [
                    'type' => RouteItemInterface::STATIC_ROUTE,
                    'routeParameters' => [
                        '_locale' => 'en_GB',
                        'testProperty' => 'universe'
                    ]
                ]
            ]);
        });
    }
}