<?php

namespace I18nBundle\PathResolver\Path;

use Pimcore\Model\Document as PimcoreDocument;

class DocumentPath extends AbstractPath
{
    /**
     * @param PimcoreDocument $currentDocument
     * @param array           $validCountries
     *
     * @return array
     */
    public function getUrls(PimcoreDocument $currentDocument = NULL, $validCountries = [])
    {
        if ($this->configuration->getConfig('mode') === 'language') {
            return $this->documentUrlsFromLanguage($currentDocument);
        }

        return $this->documentUrlsFromCountry($currentDocument, $validCountries);
    }

    /**
     * @param PimcoreDocument $currentDocument
     *
     * @return array
     */
    private function documentUrlsFromLanguage($currentDocument)
    {
        $routes = [];

        $service = new PimcoreDocument\Service;
        $translations = $service->getTranslations($currentDocument);

        if (!empty($translations)) {
            foreach ($translations as $languageSlug => $translationId) {
                $targetDocument = PimcoreDocument::getById($translationId);

                if (!$targetDocument->isPublished()) {
                    continue;
                }

                $routes[] = [
                    'language' => $languageSlug,
                    'country'  => NULL,
                    'hreflang' => $languageSlug,
                    'href'     => $this->documentRelationHelper->getDocumentFullPath($targetDocument)
                ];
            }
        }

        return $routes;
    }

    /**
     * @param PimcoreDocument $currentDocument
     * @param array           $validCountries
     *
     * @return array
     */
    private function documentUrlsFromCountry($currentDocument, $validCountries)
    {
        $globalPrefix = NULL;
        $routes = [];

        $countryIso = NULL;
        if (\Pimcore\Cache\Runtime::isRegistered('i18n.countryIso')) {
            $countryIso = \Pimcore\Cache\Runtime::get('i18n.countryIso');
        }

        $rootPath = $this->documentRelationHelper->getCurrentPageRootPath();
        $showGlobal = $countryIso === 'GLOBAL' || $this->configuration->getConfig('global') !== FALSE;

        if ($showGlobal === TRUE) {
            unset($validCountries['global']);
        }

        if ($this->configuration->getConfig('globalPrefix') !== FALSE) {
            $globalPrefix = $this->configuration->getConfig('globalPrefix');
        }

        $service = new PimcoreDocument\Service;

        $currentDocPath = $currentDocument->getFullPath();
        $currentLanguage = $currentDocument->getProperty('language');
        $currentCountry = $currentDocument->getProperty('country');

        $document = NULL;

        $isMergedCountryDocument = FALSE;

        if ($currentDocument instanceof PimcoreDocument\Hardlink\Wrapper\WrapperInterface) {
            $isMergedCountryDocument = TRUE;
        }

        $currentDocUrlPath = parse_url($currentDocPath, PHP_URL_PATH);
        $currentDocUrlPathFragments = explode('/', ltrim($currentDocUrlPath, '/'));

        //remove first slug, because it's always language or country-language
        unset($currentDocUrlPathFragments[0]);

        $currentDocumentCleanPath = join('/', $currentDocUrlPathFragments);

        /**
         * Check if page is a non-global one. maybe it's just an overwritten one from (global-)xy!
         */
        if ($isMergedCountryDocument === FALSE) {
            $globalPrefixFragment = $globalPrefix !== NULL ? $globalPrefix . '-' : '';
            $posGlobalPath = $rootPath . $globalPrefixFragment . $currentLanguage . '/' . $currentDocumentCleanPath;

            /**
             * Always (!) use the linked global doc to get translated files, even if offline!
             */
            if (PimcoreDocument\Service::pathExists($posGlobalPath)) {
                $document = PimcoreDocument::getByPath($posGlobalPath);
            }
        }

        //Now define root document to get translations from!
        if (is_null($document)) {
            $document = $currentDocument;
        }

        $translations = $service->getTranslations($document);

        $populatedTranslations = [];

        if (!empty($translations)) {
            foreach ($translations as $languageSlug => $translationId) {
                $targetDocument = PimcoreDocument::getById($translationId);
                $targetPath = $targetDocument->getFullPath();
                $targetCountry = $targetDocument->getProperty('country');
                $targetLanguage = $targetDocument->getProperty('language');

                $isGlobal = $targetCountry === 'GLOBAL';
                $globalDocumentIsActive = $targetDocument->isPublished();

                $urlPath = parse_url($targetPath, PHP_URL_PATH);
                $urlPathFragments = explode('/', ltrim($urlPath, '/'));

                //remove country-language part
                unset($urlPathFragments[0]);
                $targetPath = join('/', $urlPathFragments);

                $globalPrefixFragment = $globalPrefix !== NULL ? $globalPrefix . '-' : '';
                $slug = $globalPrefixFragment . $targetLanguage;

                //add document to custom array, we need to add them to alternate also!
                if ($isGlobal && $globalDocumentIsActive && $showGlobal) {
                    $routes[] = [
                        'language' => $targetLanguage,
                        'country'  => strtoupper($targetCountry),
                        'hreflang' => $languageSlug,
                        'href'     => \Pimcore\Tool::getHostUrl() . '/' . $slug . '/' . $targetPath
                    ];
                }

                $populatedTranslations[$languageSlug] = [
                    'id'       => (int)$translationId,
                    'isActive' => $globalDocumentIsActive,
                    'fullPath' => $targetPath
                ];
            }
        }

        foreach ($validCountries as $country) {
            //Never parse global "country". It has been parsed above.
            if ($country['country']['name'] === 'global') {
                continue;
            }

            $countryIso = $country['country']['isoCode'];
            $countryIsoLower = strtolower($countryIso);

            foreach ($country['languages'] as $activeLanguage) {
                $activeLanguageIso = $activeLanguage['iso'];

                if (!empty($populatedTranslations) && isset($populatedTranslations[$activeLanguageIso])) {
                    $translationData = $populatedTranslations[$activeLanguageIso];
                    $hardLinkDoc = TRUE;

                    $documentPath = $activeLanguageIso . '-' . $countryIsoLower . '/' . $translationData['fullPath'];

                    //is it a real document?
                    if (PimcoreDocument\Service::pathExists($rootPath . $documentPath)) {
                        $targetDocument = PimcoreDocument::getByPath($rootPath . $documentPath);
                        if (!$targetDocument->isPublished()) {
                            $hardLinkDoc = FALSE;
                        }
                    } //probably it's a global one, if so - check if active!
                    else {
                        $globalPrefixFragment = $globalPrefix !== NULL ? '-' . $globalPrefix : '';
                        $posGlobalPath = $activeLanguageIso . $globalPrefixFragment . '/' . $translationData['fullPath'];
                        $targetGlobalDocument = PimcoreDocument::getByPath($rootPath . $posGlobalPath);

                        if ($targetGlobalDocument && !$targetGlobalDocument->isPublished()) {
                            $hardLinkDoc = FALSE;
                        }
                    }

                    if ($hardLinkDoc === TRUE) {
                        $routes[] = [
                            'language' => $activeLanguageIso,
                            'country'  => $countryIso,
                            'hreflang' => $activeLanguageIso . '-' . $countryIso,
                            'href'     => \Pimcore\Tool::getHostUrl() . '/' . $activeLanguageIso . '-' . $countryIsoLower . '/' . $translationData['fullPath']
                        ];
                    }
                }
            }
        }

        return $routes;
    }
}