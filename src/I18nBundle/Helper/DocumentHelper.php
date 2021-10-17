<?php

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use Pimcore\Model\Document;
use Pimcore\Model\Site as PimcoreSite;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;

class DocumentHelper
{
    public function getDocumentFullPath(?Document $document): string
    {
        $hostUrl = Tool::getHostUrl();

        if (!$document instanceof Document) {
            return $hostUrl;
        }

        $host = $this->getDocumentUrl($document);
        $fullPath = $document->getFullPath();

        return implode('', array_filter([$host, $fullPath]));
    }

    public function getCurrentPageRootPath(): string
    {
        $rootPath = '/';
        if (PimcoreSite::isSiteRequest()) {
            $site = PimcoreSite::getCurrentSite();
            $rootPath = rtrim($site->getRootPath(), '/') . '/';
        }

        return $rootPath;
    }

    public function getDocumentLocaleData(Document $document, string $i18nType = 'language'): array
    {
        $documentCountry = null;

        if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            /** @var Document\Hardlink\Wrapper\WrapperInterface $wrapperDocument */
            $wrapperDocument = $document;
            $documentLocale = $wrapperDocument->getHardLinkSource()->getProperty('language');
        } else {
            $documentLocale = $document->getProperty('language');
        }

        if ($i18nType === 'country') {
            $documentCountry = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        $documentLanguage = $documentLocale;

        if (str_contains($documentLocale, '_')) {
            $parts = explode('_', $documentLocale);
            $documentLanguage = strtolower($parts[0]);
            if (isset($parts[1]) && !empty($parts[1])) {
                $documentCountry = strtoupper($parts[1]);
            }
        }

        return [
            'documentLocale'   => $documentLocale,
            'documentLanguage' => $documentLanguage,
            'documentCountry'  => $documentCountry
        ];
    }

    protected function getDocumentUrl(Document $document, bool $returnAsArray = false, bool $restrictToCurrentSite = true): string|array
    {
        $siteIsLanguageRoot = false;
        $url = '';

        if (!PimcoreSite::isSiteRequest()) {
            $url = rtrim(Tool::getHostUrl(), '/');

            return $returnAsArray ? ['url' => $url, 'siteIsLanguageRoot' => $siteIsLanguageRoot] : $url;
        }

        if ($restrictToCurrentSite === false || $this->isDocumentInCurrentSite($document)) {
            $site = PimcoreSite::getCurrentSite();

            //we're in the current documents domain. add Host!
            if ($site->getRootId() === $document->getId() || Frontend::isDocumentInCurrentSite($document)) {
                if ($site->getRootId() === $document->getId()) {
                    $siteIsLanguageRoot = true;
                }

                $hostUrl = Tool::getHostUrl();
            } else {
                /** @var PimcoreSite $documentSite */
                $documentSite = Frontend::getSiteForDocument($document);

                if ($documentSite->getRootId() === $document->getId()) {
                    $siteIsLanguageRoot = true;
                }

                $hostUrl = Tool::getRequestScheme() . '://' . $documentSite->getMainDomain();
            }

            $url = rtrim($hostUrl, '/');
        }

        if ($returnAsArray === true) {
            return [
                'url'                => $url,
                'siteIsLanguageRoot' => $siteIsLanguageRoot
            ];
        }

        return $url;
    }

    /**
     * Checks if document is in current site.
     * also true if given document is actually current site.
     */
    protected function isDocumentInCurrentSite(Document $document): bool
    {
        if (!PimcoreSite::isSiteRequest()) {
            return false;
        }

        if (Frontend::isDocumentInCurrentSite($document)) {
            return true;
        }

        $site = PimcoreSite::getCurrentSite();
        if ($site->getRootId() === $document->getId()) {
            return true;
        }

        return false;
    }
}
