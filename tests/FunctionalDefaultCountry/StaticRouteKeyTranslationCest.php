<?php

namespace DachcomBundle\Test\FunctionalDefaultCountry;

use DachcomBundle\Test\Support\FunctionalTester;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Exception\ZoneSiteNotFoundException;

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
            'variables' => '_locale,testProperty',
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
    public function testLocalizedStaticRouteZoneSiteException(FunctionalTester $I)
    {
        $I->haveAPageDocument('en', [], 'en');

        $staticRoute = $I->haveAStaticRoute('test_route', [
            'pattern'   => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/',
            'reverse'   => '/{%_locale}/@testKey/%testProperty',
            'action'    => 'defaultAction',
            'variables' => '_locale,testProperty',
        ]);

        $exception = ZoneSiteNotFoundException::class;
        // no available locales since static route build happens in without full bootstrap
        $exceptionMessage = 'No zone site for locale "en_GB" found. Available zone (Id 0) site locales: ';

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