<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Event\AlternateStaticRouteEvent;
use I18nBundle\I18nEvents;
use I18nBundle\Tool\System;
use Pimcore\Model\Document as PimcoreDocument;
use Pimcore\Model\Staticroute as PimcoreStaticRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StaticRoute extends AbstractPathGenerator
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param PimcoreDocument|NULL $currentDocument
     * @param bool                 $onlyShowRootLanguages
     *
     * @return array
     * @throws \Exception
     */
    public function getUrls(PimcoreDocument $currentDocument = NULL, $onlyShowRootLanguages = FALSE)
    {
        $i18nList = [];
        $routes = [];

        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            throw new \Exception('PathGenerator StaticRoute needs a valid UrlGeneratorInterface to work.');
        }

        if (!$this->requestStack->getMasterRequest() instanceof Request) {
            throw new \Exception('PathGenerator StaticRoute needs a valid Request to work.');
        }

        $currentLanguage = $currentDocument->getProperty('language');
        $currentCountry = strtolower($currentDocument->getProperty('country'));

        $route = PimcoreStaticRoute::getCurrentRoute();

        if (!$route instanceof PimcoreStaticRoute) {
            return [];
        }

        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);
        foreach ($tree as $pageInfo) {
            if (!empty($pageInfo['languageIso'])) {
                $i18nList[] = [
                    'locale'           => $pageInfo['locale'],
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => $pageInfo['countryIso'],
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'key'              => $pageInfo['key'],
                    'url'              => $pageInfo['url']
                ];
            }
        }

        $event = new AlternateStaticRouteEvent([
            'i18nList'           => $i18nList,
            'currentDocument'    => $currentDocument,
            'currentLanguage'    => $currentLanguage,
            'currentCountry'     => $currentCountry,
            'currentStaticRoute' => $route,
            'requestAttributes'  => $this->requestStack->getMasterRequest()->attributes
        ]);

        \Pimcore::getEventDispatcher()->dispatch(
            I18nEvents::PATH_ALTERNATE_STATIC_ROUTE,
            $event
        );

        $routeData = $event->getRoutes();
        if (empty($routeData)) {
            return $routes;
        }

        foreach ($i18nList as $key => $routeInfo) {

            if (!isset($routeData[$key])) {
                continue;
            }

            $staticRouteData = $routeData[$key];
            $staticRouteParams = $staticRouteData['params'];
            $staticRouteName = $staticRouteData['name'];

            if (!is_array($staticRouteParams)) {
                $staticRouteParams = [];
            }

            $link = $this->urlGenerator->generate($staticRouteName, $staticRouteParams);

            //remove locale fragment since it's already included in beginning of url
            if (!empty($routeInfo['localeUrlMapping'])) {
                $fragments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $link)));
                if ($fragments[0] === $routeInfo['localeUrlMapping']) {
                    unset($fragments[0]);
                    $addSlash = substr($link, 0, 1) === DIRECTORY_SEPARATOR;
                    $link = System::joinPath($fragments, $addSlash);
                }
            }

            $finalStoreData = [
                'languageIso'      => $routeInfo['languageIso'],
                'countryIso'       => $routeInfo['countryIso'],
                'hrefLang'         => $routeInfo['hrefLang'],
                'localeUrlMapping' => $routeInfo['localeUrlMapping'],
                'key'              => $routeInfo['key'],
                'url'              => System::joinPath([$routeInfo['url'], $link])
            ];

            $routes[] = $finalStoreData;

        }

        return $routes;
    }

}
