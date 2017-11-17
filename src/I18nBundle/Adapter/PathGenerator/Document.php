<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Definitions;
use I18nBundle\Tool\System;
use Pimcore\Model\Document as PimcoreDocument;

class Document extends AbstractPathGenerator
{
    /**
     * @param PimcoreDocument $currentDocument
     * @param bool            $onlyShowRootLanguages
     *
     * @return array
     */
    public function getUrls(PimcoreDocument $currentDocument = NULL, $onlyShowRootLanguages = FALSE)
    {
        if ($this->zoneManager->getCurrentZoneInfo('mode') === 'language') {
            return $this->documentUrlsFromLanguage($currentDocument, $onlyShowRootLanguages);
        }

        return $this->documentUrlsFromCountry($currentDocument, $onlyShowRootLanguages);
    }

    /**
     * @param PimcoreDocument $currentDocument
     * @param bool            $onlyShowRootLanguages
     *
     * @return array
     */
    private function documentUrlsFromLanguage(PimcoreDocument $currentDocument, $onlyShowRootLanguages = FALSE)
    {
        $routes = [];
        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);
        $rootDocumentId = array_search($currentDocument->getId(), array_column($tree, 'id'));

        // case 1: no deep linking requested. only return root pages!
        // case 2: current document is a root page ($rootDocumentId) - only return root pages!
        if ($onlyShowRootLanguages === TRUE || $rootDocumentId !== FALSE) {
            foreach ($tree as $pageInfo) {
                if (empty($pageInfo['languageIso'])) {
                    continue;
                }

                $routes[] = [
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => NULL,
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'key'              => $pageInfo['key'],
                    'url'              => $pageInfo['url']
                ];
            }

            return $routes;
        }

        $service = new PimcoreDocument\Service;
        $translations = $service->getTranslations($currentDocument);

        foreach ($tree as $pageInfo) {

            if (empty($pageInfo['languageIso'])) {
                continue;
            }

            $pageInfoLocale = $pageInfo['languageIso'];
            if (isset($translations[$pageInfoLocale])) {

                try {
                    $document = PimcoreDocument::getById($translations[$pageInfoLocale]);
                } catch (\Exception $e) {
                    continue;
                }

                if (!$document->isPublished()) {
                    continue;
                }

                $routes[] = [
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => NULL,
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'key'              => $document->getKey(),
                    'url'              => System::joinPath([$pageInfo['url'], $document->getKey()])
                ];
            }
        }

        return $routes;
    }

    /**
     * @param PimcoreDocument $currentDocument
     * @param bool            $onlyShowRootLanguages
     *
     * @return array
     */
    private function documentUrlsFromCountry(PimcoreDocument $currentDocument, $onlyShowRootLanguages = FALSE)
    {
        $routes = [];

        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);
        $rootDocumentId = array_search($currentDocument->getId(), array_column($tree, 'id'));

        if ($onlyShowRootLanguages === TRUE || $rootDocumentId !== FALSE) {
            foreach ($tree as $pageInfo) {
                if (!empty($pageInfo['countryIso'])) {
                    $routes[] = [
                        'languageIso'      => $pageInfo['languageIso'],
                        'countryIso'       => $pageInfo['countryIso'],
                        'hrefLang'         => $pageInfo['hrefLang'],
                        'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                        'key'              => $pageInfo['key'],
                        'url'              => $pageInfo['url']
                    ];
                }
            }
        } else {

            $hardLinksToCheck = [];
            $service = new PimcoreDocument\Service;
            $translations = $service->getTranslations($currentDocument);

            //if no translation has been found, add document itself:
            if (empty($translations) && $currentDocument->hasProperty('language')) {
                if ($currentDocument instanceof PimcoreDocument\Hardlink\Wrapper\WrapperInterface) {
                    $locale = $currentDocument->getHardLinkSource()->getSourceDocument()->getProperty('language');
                } else {
                    $locale = $currentDocument->getProperty('language');
                }

                $translations = [$locale => $currentDocument->getId()];
            }

            foreach ($tree as $pageInfo) {
                if (empty($pageInfo['languageIso']) || empty($pageInfo['countryIso'])) {
                    continue;
                }

                $pageInfoLocale = $pageInfo['languageIso'];
                if ($pageInfo['countryIso'] !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    $pageInfoLocale .= '_' . $pageInfo['countryIso'];
                }

                if (isset($translations[$pageInfoLocale])) {

                    try {
                        $document = PimcoreDocument::getById($translations[$pageInfoLocale]);
                    } catch (\Exception $e) {
                        continue;
                    }

                    if (!$document->isPublished()) {
                        continue;
                    }

                    $routes[] = [
                        'languageIso'      => $pageInfo['languageIso'],
                        'countryIso'       => $pageInfo['countryIso'],
                        'hrefLang'         => $pageInfo['hrefLang'],
                        'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                        'key'              => $document->getKey(),
                        'url'              => System::joinPath([$pageInfo['url'], $document->getKey()])
                    ];
                    //document does not exist.
                } else {
                    $hardLinksToCheck[] = $pageInfo;
                }
            }

            if (!empty($hardLinksToCheck)) {
                foreach ($hardLinksToCheck as $hardLinkWrapper) {
                    $sameLanguageContext = array_search($hardLinkWrapper['languageIso'], array_column($routes, 'languageIso'));
                    if ($sameLanguageContext === FALSE || !isset($routes[$sameLanguageContext])) {
                        continue;
                    }

                    $languageContext = $routes[$sameLanguageContext];
                    $posGlobalPath = System::joinPath([$hardLinkWrapper['fullPath'], $languageContext['key']]);

                    //always continue: could be disabled or isn't linked via translations.
                    if (PimcoreDocument\Service::pathExists($posGlobalPath)) {
                        continue;
                    }

                    $routes[] = [
                        'languageIso'      => $hardLinkWrapper['languageIso'],
                        'countryIso'       => $hardLinkWrapper['countryIso'],
                        'hrefLang'         => $hardLinkWrapper['hrefLang'],
                        'localeUrlMapping' => $hardLinkWrapper['localeUrlMapping'],
                        'key'              => $languageContext['key'],
                        'url'              => System::joinPath([$hardLinkWrapper['url'], $languageContext['key']])
                    ];
                }
            }
        }

        return $routes;
    }
}