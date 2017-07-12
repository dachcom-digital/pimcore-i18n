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
     * @param array                $validCountries
     *
     * @return array
     * @throws \Exception
     */
    public function getUrls(PimcoreDocument $currentDocument = NULL, $validCountries = [])
    {
        $globalPrefix = NULL;
        $routes = [];
        $i18nList = [];

        if(!$this->urlGenerator instanceof UrlGeneratorInterface) {
            throw new \Exception('PathGenerator StaticRoute needs a valid UrlGeneratorInterface to work.');
        }

        if(!$this->request instanceof Request) {
            throw new \Exception('PathGenerator StaticRoute needs a valid Request to work.');
        }

        if ($this->zoneManager->getCurrentZoneInfo('global_prefix') !== FALSE) {
            $globalPrefix = $this->zoneManager->getCurrentZoneInfo('global_prefix');
        }

        $currentLanguage = $currentDocument->getProperty('language');
        $currentCountry = strtolower($currentDocument->getProperty('country'));

        //to cycle through global pages!
        $showGlobal = isset($validCountries['global']);

        $route = PimcoreStaticRoute::getCurrentRoute();

        if (!$route instanceof PimcoreStaticRoute) {
            return [];
        }

        $rootConnectedDocuments = $this->documentHelper->getRootConnectedDocuments();

        if ($showGlobal) {
            foreach ($rootConnectedDocuments as $languageDoc) {

                //only show global pages.
                if ($languageDoc['countryIso'] !== 'GLOBAL') {
                    continue;
                }

                $i18nList[] = [
                    'langIso'            => $languageDoc['langIso'],
                    'countryIso'         => $languageDoc['countryIso'],
                    'hostUrl'            => $languageDoc['hostUrl'],
                    'siteIsLanguageRoot' => $languageDoc['siteIsLanguageRoot'],
                    'countryName'        => 'global'
                ];
            }
        }

        foreach ($validCountries as $countryData) {
            //Never parse global "country". It has been parsed above.
            if ($countryData['country']['name'] === 'global') {
                continue;
            }

            $countryIso = $countryData['country']['isoCode'];
            $countryName = $countryData['country']['name'];

            foreach ($countryData['languages'] as $activeLanguage) {
                $i18nList[] = [
                    'langIso'            => $activeLanguage['iso'],
                    'hostUrl'            => \Pimcore\Tool::getHostUrl(),
                    'siteIsLanguageRoot' => FALSE,
                    'countryName'        => $countryName,
                    'countryIso'         => $countryIso
                ];
            }
        }

        $event = new GenericEvent($this, [
            'i18nList'          => $i18nList,
            'globalPrefix'      => $globalPrefix,
            'currentDocument'   => $currentDocument,
            'currentLanguage'   => $currentLanguage,
            'currentCountry'    => $currentCountry,
            'validCountries'    => $validCountries,
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

                        $staticRouteParams['siteIsLanguageRoot'] = $routeInfo['siteIsLanguageRoot'];

                        $link = $this->urlGenerator->generate($staticRouteName, $staticRouteParams);

                        if ($this->zoneManager->getCurrentZoneInfo('mode') === 'country') {
                            $hrefLangCode = $routeInfo['langIso'] . ($routeInfo['countryIso'] !== 'GLOBAL' ? '-' . $routeInfo['countryIso'] : '');
                        } else {
                            $hrefLangCode = $routeInfo['langIso'];
                        }

                        $finalStoreData = [
                            'language' => $routeInfo['langIso'],
                            'country'  => $routeInfo['countryIso'],
                            'hreflang' => $hrefLangCode,
                            'href'     => $routeInfo['hostUrl'] . $link
                        ];

                        $routes[] = $finalStoreData;
                    }
                }
            }
        }

        return $routes;
    }

}