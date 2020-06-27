<?php

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use Pimcore\Model\Document;
use Pimcore\Model\Site as PimcoreSite;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;

class DocumentHelper
{
    /**
     * Get Documents Url and Path.
     *
     * @param Document $document
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDocumentFullPath($document = null)
    {
        $hostUrl = Tool::getHostUrl();

        if (!$document instanceof Document) {
            return $hostUrl;
        }

        $host = $this->getDocumentUrl($document);
        $fullPath = $document->getFullPath();

        return implode('', array_filter([$host, $fullPath]));
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getCurrentPageRootPath()
    {
        $rootPath = '/';
        if (PimcoreSite::isSiteRequest()) {
            $site = PimcoreSite::getCurrentSite();
            $rootPath = rtrim($site->getRootPath(), '/') . '/';
        }

        return $rootPath;
    }

    /**
     * @param Document $document
     * @param string   $i18nType
     *
     * @return array
     */
    public function getDocumentLocaleData(Document $document, $i18nType = 'language')
    {
        $documentLocale = null;
        $documentLanguage = null;
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

        if (strpos($documentLocale, '_') !== false) {
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

    /**
     *  Get Documents Url without it's own path.
     *
     * @param Document $document
     * @param bool     $returnAsArray
     * @param bool     $restrictToCurrentSite
     *
     * @return array string document url without trailing slash
     *
     * @throws \Exception
     */
    protected function getDocumentUrl($document = null, $returnAsArray = false, $restrictToCurrentSite = true)
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
     *
     * @param Document $document
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function isDocumentInCurrentSite($document)
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
