<?php

namespace I18nBundle\Adapter\PathGenerator;

use Pimcore\Model\Document as PimcoreDocument;
use Pimcore\Model\Staticroute as PimcoreStaticRoute;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StaticRoute extends AbstractPathGenerator
{
    /**
     * @var Request
     */
    var $request;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @param Request $request
     */
    public function setMasterRequest(Request $request)
    {
        $this->request = $request;
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
        $globalPrefix = NULL;
        $i18nList = [];
        $routes = [];

        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            throw new \Exception('PathGenerator StaticRoute needs a valid UrlGeneratorInterface to work.');
        }

        if (!$this->request instanceof Request) {
            throw new \Exception('PathGenerator StaticRoute needs a valid Request to work.');
        }

        if ($this->zoneManager->getCurrentZoneInfo('global_prefix') !== FALSE) {
            $globalPrefix = $this->zoneManager->getCurrentZoneInfo('global_prefix');
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
                    '_locale'     => $pageInfo['locale'],
                    '_localeUrl'  => strtolower(str_replace('_', '-', $pageInfo['locale'])),
                    'languageIso' => $pageInfo['languageIso'],
                    'countryIso'  => $pageInfo['countryIso'],
                    'hrefLang'    => $pageInfo['hrefLang'],
                    'key'         => $pageInfo['key'],
                    'url'         => $pageInfo['url']
                ];
            }
        }

        $event = new GenericEvent($this, [
            'i18nList'          => $i18nList,
            'globalPrefix'      => $globalPrefix,
            'currentDocument'   => $currentDocument,
            'currentLanguage'   => $currentLanguage,
            'currentCountry'    => $currentCountry,
            'route'             => $route,
            'requestAttributes' => $this->request->attributes->all()
        ]);

        \Pimcore::getEventDispatcher()->dispatch(
            'i18n.path.staticRoute.alternate',
            $event
        );

        if ($event->hasArgument('i18nData')) {
            $routeData = $event->getArgument('i18nData');
            if (is_array($routeData)) {
                foreach ($i18nList as $key => $routeInfo) {
                    if (isset($routeData[$key]) && isset($routeData[$key]['staticRoute'])) {
                        $staticRouteData = $routeData[$key]['staticRoute'];
                        $staticRouteParams = $staticRouteData['params'];
                        $staticRouteName = $staticRouteData['name'];

                        if (!is_array($staticRouteParams)) {
                            $staticRouteParams = [];
                        }

                        //$staticRouteParams['siteIsLanguageRoot'] = $routeInfo['siteIsLanguageRoot'];

                        $link = $this->urlGenerator->generate($staticRouteName, $staticRouteParams);

                        $finalStoreData = [
                            'language' => $routeInfo['languageIso'],
                            'country'  => $routeInfo['countryIso'],
                            'hrefLang' => $routeInfo['hrefLang'],
                            'url'      => $routeInfo['hostUrl'] . $link
                        ];

                        $routes[] = $finalStoreData;
                    }
                }
            }
        }

        return $routes;
    }

}