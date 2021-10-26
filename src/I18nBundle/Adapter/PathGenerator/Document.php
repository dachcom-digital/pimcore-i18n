<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Model\I18nSiteInterface;
use I18nBundle\Tool\System;
use Pimcore\Model\Document as PimcoreDocument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Document extends AbstractPathGenerator
{
    protected array $options;
    protected array $cachedUrls = [];

    public function configureOptions(OptionsResolver $options): void
    {
        $options
            ->setDefaults(['document'])
            ->setRequired(['document'])
            ->setAllowedTypes('document', [PimcoreDocument::class]);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getUrls(bool $onlyShowRootLanguages = false): array
    {
        $document = $this->options['document'];

        if (isset($this->cachedUrls[$document->getId()])) {
            return $this->cachedUrls[$document->getId()];
        }

        if ($this->zone->getMode() === 'language') {
            $urls = $this->documentUrlsFromLanguage($document, $onlyShowRootLanguages);
        } else {
            $urls = $this->documentUrlsFromCountry($document, $onlyShowRootLanguages);
        }

        $this->cachedUrls[$document->getId()] = $urls;

        return $urls;
    }

    private function documentUrlsFromLanguage(PimcoreDocument $document, bool $onlyShowRootLanguages = false): array
    {
        $routes = [];

        try {
            $zoneSites = $this->zone->getSites(true);
        } catch (\Exception $e) {
            return [];
        }

        $rootDocumentIndexId = array_search($document->getId(), array_map(static function (I18nSiteInterface $site) {
            return $site->getRootId();
        }, $zoneSites), true);

        // case 1: no deep linking requested. only return root pages!
        // case 2: current document is a root page ($rootDocumentIndexId) - only return root pages!
        if ($onlyShowRootLanguages === true || $rootDocumentIndexId !== false) {
            foreach ($zoneSites as $zoneSite) {

                if (empty($zoneSite->getLanguageIso())) {
                    continue;
                }

                $routes[] = [
                    'languageIso'      => $zoneSite->getLanguageIso(),
                    'countryIso'       => null,
                    'locale'           => $zoneSite->getLocale(),
                    'hrefLang'         => $zoneSite->getHrefLang(),
                    'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                    'key'              => $document->getKey(),
                    'url'              => $zoneSite->getUrl()
                ];
            }

            return $routes;
        }

        $service = new PimcoreDocument\Service();
        $translations = $service->getTranslations($document);

        foreach ($zoneSites as $zoneSite) {

            if (empty($zoneSite->getLocale())) {
                continue;
            }

            $pageInfoLocale = $zoneSite->getLocale();
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
                    $url = System::joinPath([$zoneSite->getDomainUrl(), $relativePath]);
                } else {
                    // map paths
                    $documentPath = $document->getRealPath() . $document->getKey();
                    $relativePath = preg_replace('/^' . preg_quote($zoneSite->getFullPath(), '/') . '/', '', $documentPath);
                    $url = System::joinPath([$zoneSite->getUrl(), $relativePath]);
                }

                $routes[] = [
                    'languageIso'      => $zoneSite->getLanguageIso(),
                    'countryIso'       => null,
                    'locale'           => $zoneSite->getLocale(),
                    'hrefLang'         => $zoneSite->getHrefLang(),
                    'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                    'key'              => $document->getKey(),
                    'relativePath'     => $relativePath,
                    'url'              => $url
                ];
            }
        }

        return $routes;
    }

    private function documentUrlsFromCountry(PimcoreDocument $document, bool $onlyShowRootLanguages = false): array
    {
        $routes = [];

        try {
            $zoneSites = $this->zone->getSites(true);
        } catch (\Exception $e) {
            return [];
        }

        $rootDocumentIndexId = array_search($document->getId(), array_map(static function (I18nSiteInterface $site) {
            return $site->getRootId();
        }, $zoneSites), true);

        if ($onlyShowRootLanguages === true || $rootDocumentIndexId !== false) {

            foreach ($zoneSites as $zoneSite) {
                if (!empty($zoneSite->getCountryIso())) {
                    $routes[] = [
                        'languageIso'      => $zoneSite->getLanguageIso(),
                        'countryIso'       => $zoneSite->getCountryIso(),
                        'locale'           => $zoneSite->getLocale(),
                        'hrefLang'         => $zoneSite->getHrefLang(),
                        'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                        'key'              => $document->getKey(),
                        'url'              => $zoneSite->getUrl()
                    ];
                }
            }

            return $routes;
        }

        $hardLinkZoneSitesToCheck = [];
        $service = new PimcoreDocument\Service();
        $translations = $service->getTranslations($document);

        //if no translation has been found, add document itself:
        if (empty($translations) && $document->hasProperty('language')) {
            if ($document instanceof PimcoreDocument\Hardlink\Wrapper\WrapperInterface) {
                /** @var PimcoreDocument\Hardlink\Wrapper\WrapperInterface $wrapperDocument */
                $wrapperDocument = $document;
                $locale = $wrapperDocument->getHardLinkSource()->getSourceDocument()?->getProperty('language');
            } else {
                $locale = $document->getProperty('language');
            }

            $translations = [$locale => $document->getId()];
        }

        foreach ($zoneSites as $zoneSite) {

            if (empty($zoneSite->getLocale())) {
                continue;
            }

            $pageInfoLocale = $zoneSite->getLocale();

            // document/translation does not exist.
            // if page info is type of "hardlink", we need to add them to a second check
            if (!isset($translations[$pageInfoLocale])) {
                if ($zoneSite->getType() === 'hardlink') {
                    $hardLinkZoneSitesToCheck[] = $zoneSite;
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
                $url = System::joinPath([$zoneSite->getDomainUrl(), $relativePath]);
            } else {
                //map paths
                $documentPath = $document->getRealPath() . $document->getKey();
                $relativePath = preg_replace('/^' . preg_quote($zoneSite->getFullPath(), '/') . '/', '', $documentPath);
                $url = System::joinPath([$zoneSite->getUrl(), $relativePath]);
            }

            $routes[] = [
                'languageIso'      => $zoneSite->getLanguageIso(),
                'countryIso'       => $zoneSite->getCountryIso(),
                'locale'           => $zoneSite->getLocale(),
                'hrefLang'         => $zoneSite->getHrefLang(),
                'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                'key'              => $document->getKey(),
                'relativePath'     => $relativePath,
                'hasPrettyUrl'     => $hasPrettyUrl,
                'url'              => $url
            ];
        }

        if (count($hardLinkZoneSitesToCheck) === 0) {
            return $routes;
        }

        foreach ($hardLinkZoneSitesToCheck as $hardLinkZoneSiteWrapper) {

            $sameLanguageContext = array_search($hardLinkZoneSiteWrapper->getLanguageIso(), array_column($routes, 'languageIso'), true);
            if ($sameLanguageContext === false || !isset($routes[$sameLanguageContext])) {
                continue;
            }

            $languageContext = $routes[$sameLanguageContext];
            $posGlobalPath = System::joinPath([$hardLinkZoneSiteWrapper->getFullPath(), $languageContext['relativePath']]);

            // case 1: only add hardlinks check if document has no pretty url => we can't guess pretty urls by magic.
            // case 2: always continue: could be disabled or isn't linked via translations.
            if ($languageContext['hasPrettyUrl'] === true || PimcoreDocument\Service::pathExists($posGlobalPath)) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $hardLinkZoneSiteWrapper->getLanguageIso(),
                'countryIso'       => $hardLinkZoneSiteWrapper->getCountryIso(),
                'locale'           => $hardLinkZoneSiteWrapper->getLocale(),
                'hrefLang'         => $hardLinkZoneSiteWrapper->getHrefLang(),
                'localeUrlMapping' => $hardLinkZoneSiteWrapper->getLocaleUrlMapping(),
                'key'              => $languageContext['key'],
                'url'              => System::joinPath([$hardLinkZoneSiteWrapper->getUrl(), $languageContext['relativePath']])
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
