<?php

namespace I18nBundle\PathResolver\Path;

use Pimcore\Model\Document as PimcoreDocument;
use Pimcore\Model\Staticroute;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

class StaticRoutePath extends AbstractPath
{
    /**
     * @var Request
     */
    var $masterRequest;

    /**
     * @param Request $masterRequest
     */
    public function setMasterRequest(Request $masterRequest)
    {
        $this->masterRequest = $masterRequest;
    }

    /**
     * @param PimcoreDocument $currentDocument
     * @param array           $validCountries
     *
     * @return array|mixed
     */
    public function getUrls(PimcoreDocument $currentDocument = NULL, $validCountries = [])
    {
        $globalPrefix = NULL;
        $routes = [];
        $i18nList = [];

        if ($this->configuration->getConfig('globalPrefix') !== FALSE) {
            $globalPrefix = $this->configuration->getConfig('globalPrefix');
        }

        $currentLanguage = $currentDocument->getProperty('language');
        $currentCountry = strtolower($currentDocument->getProperty('country'));

        //to cycle through global pages!
        $showGlobal = isset($validCountries['global']);

        $route = Staticroute::getCurrentRoute();

        if (!$route instanceof Staticroute) {
            return $routes;
        }

        $rootConnectedDocuments = $this->documentRelationHelper->getRootConnectedDocuments();

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
            'requestAttributes' => $this->masterRequest->attributes->all()
        ]);

        \Pimcore::getEventDispatcher()->dispatch(
            'i18n.staticRoute.alternate',
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

                        $link = $this->generator->generate($staticRouteName, $staticRouteParams);

                        if ($this->configuration->getConfig('mode') === 'country') {
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