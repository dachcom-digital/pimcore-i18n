<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Definitions;
use I18nBundle\Event\AlternateStaticRouteEvent;
use I18nBundle\I18nEvents;
use I18nBundle\Tool\System;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document as PimcoreDocument;
use Pimcore\Model\Staticroute as PimcoreStaticRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StaticRoute extends AbstractPathGenerator
{
    /**
     * @var array
     */
    protected $cachedUrls = [];

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
     * @param PimcoreDocument|null $currentDocument
     * @param bool                 $onlyShowRootLanguages
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getUrls(PimcoreDocument $currentDocument = null, $onlyShowRootLanguages = false)
    {
        if (isset($this->cachedUrls[$currentDocument->getId()])) {
            return $this->cachedUrls[$currentDocument->getId()];
        }

        $i18nList = [];
        $routes = [];

        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            throw new \Exception('PathGenerator StaticRoute needs a valid UrlGeneratorInterface to work.');
        }

        if (!$this->requestStack->getMasterRequest() instanceof Request) {
            throw new \Exception('PathGenerator StaticRoute needs a valid Request to work.');
        }

        $currentLanguage = $currentDocument->getProperty('language');
        $currentCountry = null;

        if ($this->zoneManager->getCurrentZoneInfo('mode') === 'country') {
            $currentCountry = strtolower(Definitions::INTERNATIONAL_COUNTRY_NAMESPACE);
        }

        if (strpos($currentLanguage, '_') !== false) {
            $parts = explode('_', $currentLanguage);
            if (isset($parts[1]) && !empty($parts[1])) {
                $currentCountry = strtolower($parts[1]);
            }
        }

        $route = PimcoreStaticRoute::getCurrentRoute();

        if (!$route instanceof PimcoreStaticRoute) {
            return [];
        }

        $tree = $this->zoneManager->getCurrentZoneDomains(true);

        //create custom list for event ($i18nList) - do not include all the zone config stuff.
        foreach ($tree as $pageInfo) {
            if (!empty($pageInfo['languageIso'])) {
                $i18nList[] = [
                    'locale'           => $pageInfo['locale'],
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => $pageInfo['countryIso'],
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'url'              => $pageInfo['url'],
                    'domainUrl'        => $pageInfo['domainUrl']
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

            $link = $this->generateLink($staticRouteData);

            if ($link === null) {
                continue;
            }

            // use domainUrl element since $link already comes with the locale part!
            $url = strpos($link, 'http' !== false) ? $link : System::joinPath([$routeInfo['domainUrl'], $link]);

            $finalStoreData = [
                'languageIso'      => $routeInfo['languageIso'],
                'countryIso'       => $routeInfo['countryIso'],
                'locale'           => $routeInfo['locale'],
                'hrefLang'         => $routeInfo['hrefLang'],
                'localeUrlMapping' => $routeInfo['localeUrlMapping'],
                'url'              => $url
            ];

            $routes[] = $finalStoreData;
        }

        $this->cachedUrls[$currentDocument->getId()] = $routes;

        return $routes;
    }

    /**
     * @param $staticRouteData
     *
     * @return string|null
     */
    protected function generateLink($staticRouteData)
    {
        $staticRouteParams = $staticRouteData['params'];

        if (!is_array($staticRouteParams)) {
            $staticRouteParams = [];
        }

        if (isset($staticRouteData['name']) && is_string($staticRouteData['name'])) {
            $staticRouteName = $staticRouteData['name'];
            return $this->urlGenerator->generate($staticRouteName, $staticRouteParams);
        }

        if (!isset($staticRouteData['object']) || !$staticRouteData['object'] instanceof Concrete) {
            return null;
        }

        /** @var Concrete $object */
        $object = $staticRouteData['object'];
        $linkGenerator = $object->getClass()->getLinkGenerator();

        if (!$linkGenerator instanceof LinkGeneratorInterface) {
            return null;
        }

        return $linkGenerator->generate($object, $staticRouteParams);
    }
}
