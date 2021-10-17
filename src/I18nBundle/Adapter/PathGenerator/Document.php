<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Tool\System;
use Pimcore\Model\Document as PimcoreDocument;

class Document extends AbstractPathGenerator
{
    protected array $cachedUrls = [];

    public function getUrls(PimcoreDocument $currentDocument, bool $onlyShowRootLanguages = false): array
    {
        if (isset($this->cachedUrls[$currentDocument->getId()])) {
            return $this->cachedUrls[$currentDocument->getId()];
        }

        try {
            $mode = $this->zoneManager->getCurrentZoneInfo('mode');
        } catch (\Exception $e) {
            return [];
        }

        if ($mode === 'language') {
            $urls = $this->documentUrlsFromLanguage($currentDocument, $onlyShowRootLanguages);
        } else {
            $urls = $this->documentUrlsFromCountry($currentDocument, $onlyShowRootLanguages);
        }

        $this->cachedUrls[$currentDocument->getId()] = $urls;

        return $urls;
    }

    private function documentUrlsFromLanguage(PimcoreDocument $currentDocument, bool $onlyShowRootLanguages = false): array
    {
        $routes = [];

        try {
            $tree = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return [];
        }

        $rootDocumentId = array_search($currentDocument->getId(), array_column($tree, 'id'), true);

        // case 1: no deep linking requested. only return root pages!
        // case 2: current document is a root page ($rootDocumentId) - only return root pages!
        if ($onlyShowRootLanguages === true || $rootDocumentId !== false) {
            foreach ($tree as $pageInfo) {
                if (empty($pageInfo['languageIso'])) {
                    continue;
                }

                $routes[] = [
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => null,
                    'locale'           => $pageInfo['locale'],
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'key'              => $currentDocument->getKey(),
                    'url'              => $pageInfo['url']
                ];
            }

            return $routes;
        }

        $service = new PimcoreDocument\Service();
        $translations = $service->getTranslations($currentDocument);

        foreach ($tree as $pageInfo) {
            if (empty($pageInfo['locale'])) {
                continue;
            }

            $pageInfoLocale = $pageInfo['locale'];
            if (isset($translations[$pageInfoLocale])) {
                try {
                    /** @var PimcoreDocument\Page $document */
                    $document = PimcoreDocument::getById($translations[$pageInfoLocale]);
                } catch (\Exception $e) {
                    continue;
                }

                if (!$document->isPublished()) {
                    continue;
                }

                if ($this->hasPrettyUrl($document) === true) {
                    $relativePath = $document->getPrettyUrl();
                    $url = System::joinPath([$pageInfo['domainUrl'], $relativePath]);
                } else {
                    //map paths
                    $documentPath = $document->getRealPath() . $document->getKey();
                    $relativePath = preg_replace('/^' . preg_quote($pageInfo['fullPath'], '/') . '/', '', $documentPath);
                    $url = System::joinPath([$pageInfo['url'], $relativePath]);
                }

                $routes[] = [
                    'languageIso'      => $pageInfo['languageIso'],
                    'countryIso'       => null,
                    'locale'           => $pageInfo['locale'],
                    'hrefLang'         => $pageInfo['hrefLang'],
                    'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                    'key'              => $document->getKey(),
                    'relativePath'     => $relativePath,
                    'url'              => $url
                ];
            }
        }

        return $routes;
    }

    private function documentUrlsFromCountry(PimcoreDocument $currentDocument, bool $onlyShowRootLanguages = false): array
    {
        $routes = [];

        try {
            $tree = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return [];
        }

        $rootDocumentId = array_search($currentDocument->getId(), array_column($tree, 'id'), true);

        if ($onlyShowRootLanguages === true || $rootDocumentId !== false) {

            foreach ($tree as $pageInfo) {
                if (!empty($pageInfo['countryIso'])) {
                    $routes[] = [
                        'languageIso'      => $pageInfo['languageIso'],
                        'countryIso'       => $pageInfo['countryIso'],
                        'locale'           => $pageInfo['locale'],
                        'hrefLang'         => $pageInfo['hrefLang'],
                        'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                        'key'              => $currentDocument->getKey(),
                        'url'              => $pageInfo['url']
                    ];
                }
            }

            return $routes;
        }

        $hardLinksToCheck = [];
        $service = new PimcoreDocument\Service();
        $translations = $service->getTranslations($currentDocument);

        //if no translation has been found, add document itself:
        if (empty($translations) && $currentDocument->hasProperty('language')) {
            if ($currentDocument instanceof PimcoreDocument\Hardlink\Wrapper\WrapperInterface) {
                /** @var PimcoreDocument\Hardlink\Wrapper\WrapperInterface $wrapperDocument */
                $wrapperDocument = $currentDocument;
                $locale = $wrapperDocument->getHardLinkSource()->getSourceDocument()->getProperty('language');
            } else {
                $locale = $currentDocument->getProperty('language');
            }

            $translations = [$locale => $currentDocument->getId()];
        }

        foreach ($tree as $pageInfo) {

            if (empty($pageInfo['locale'])) {
                continue;
            }

            $pageInfoLocale = $pageInfo['locale'];

            // document/translation does not exist.
            // if page info is type of "hardlink", we need to add them to a second check
            if (!isset($translations[$pageInfoLocale])) {
                if ($pageInfo['type'] === 'hardlink') {
                    $hardLinksToCheck[] = $pageInfo;
                }
                continue;
            }

            try {
                /** @var PimcoreDocument\Page $document */
                $document = PimcoreDocument::getById($translations[$pageInfoLocale]);
            } catch (\Exception $e) {
                continue;
            }

            if (!$document->isPublished()) {
                continue;
            }

            $hasPrettyUrl = false;
            if ($this->hasPrettyUrl($document) === true) {
                $hasPrettyUrl = true;
                $relativePath = $document->getPrettyUrl();
                $url = System::joinPath([$pageInfo['domainUrl'], $relativePath]);
            } else {
                //map paths
                $documentPath = $document->getRealPath() . $document->getKey();
                $relativePath = preg_replace('/^' . preg_quote($pageInfo['fullPath'], '/') . '/', '', $documentPath);
                $url = System::joinPath([$pageInfo['url'], $relativePath]);
            }

            $routes[] = [
                'languageIso'      => $pageInfo['languageIso'],
                'countryIso'       => $pageInfo['countryIso'],
                'locale'           => $pageInfo['locale'],
                'hrefLang'         => $pageInfo['hrefLang'],
                'localeUrlMapping' => $pageInfo['localeUrlMapping'],
                'key'              => $document->getKey(),
                'relativePath'     => $relativePath,
                'hasPrettyUrl'     => $hasPrettyUrl,
                'url'              => $url
            ];
        }

        if (empty($hardLinksToCheck)) {
            return $routes;
        }

        foreach ($hardLinksToCheck as $hardLinkWrapper) {

            $sameLanguageContext = array_search($hardLinkWrapper['languageIso'], array_column($routes, 'languageIso'), true);
            if ($sameLanguageContext === false || !isset($routes[$sameLanguageContext])) {
                continue;
            }

            $languageContext = $routes[$sameLanguageContext];
            $posGlobalPath = System::joinPath([$hardLinkWrapper['fullPath'], $languageContext['relativePath']]);

            // case 1: only add hardlinks check if document has no pretty url => we can't guess pretty urls by magic.
            // case 2: always continue: could be disabled or isn't linked via translations.
            if ($languageContext['hasPrettyUrl'] === true || PimcoreDocument\Service::pathExists($posGlobalPath)) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $hardLinkWrapper['languageIso'],
                'countryIso'       => $hardLinkWrapper['countryIso'],
                'locale'           => $hardLinkWrapper['locale'],
                'hrefLang'         => $hardLinkWrapper['hrefLang'],
                'localeUrlMapping' => $hardLinkWrapper['localeUrlMapping'],
                'key'              => $languageContext['key'],
                'url'              => System::joinPath([$hardLinkWrapper['url'], $languageContext['relativePath']])
            ];
        }

        return $routes;
    }

    private function hasPrettyUrl(PimcoreDocument $document): bool
    {
        if ($document instanceof PimcoreDocument\Page) {
            return !empty($document->getPrettyUrl()) && $document->getPrettyUrl() !== '';
        }

        return false;
    }
}
