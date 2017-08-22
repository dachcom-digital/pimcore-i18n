<?php

namespace I18nBundle\Helper;

use Pimcore\Model\Site as PimcoreSite;

class DocumentHelper
{
    /**
     * Get Documents Url and Path
     *
     * @param \Pimcore\Model\Document $document
     *
     * @return string
     */
    public function getDocumentFullPath($document = NULL)
    {
        $hostUrl = \Pimcore\Tool::getHostUrl();

        if (!$document instanceof \Pimcore\Model\Document) {
            return $hostUrl;
        }

        $host = $this->getDocumentUrl($document);
        $fullPath = $document->getFullPath();

        return implode('', array_filter([$host, $fullPath]));
    }

    /**
     * @return string
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
     *  Get Documents Url without it's own path
     *
     * @param \Pimcore\Model\Document $document
     * @param bool                    $returnAsArray
     * @param bool                    $restrictToCurrentSite
     *
     * @return array string document url without trailing slash
     */
    private function getDocumentUrl($document = NULL, $returnAsArray = FALSE, $restrictToCurrentSite = TRUE)
    {
        $siteIsLanguageRoot = FALSE;
        $url = '';

        if (!\Pimcore\Model\Site::isSiteRequest()) {
            $url = rtrim(\Pimcore\Tool::getHostUrl(), '/');
            return $returnAsArray ? ['url' => $url, 'siteIsLanguageRoot' => $siteIsLanguageRoot] : $url;
        }

        if ($restrictToCurrentSite === FALSE || $this->isDocumentInCurrentSite($document)) {
            $site = \Pimcore\Model\Site::getCurrentSite();

            //we're in the current documents domain. add Host!
            if ($site->getRootId() === $document->getId() || \Pimcore\Tool\Frontend::isDocumentInCurrentSite($document)) {
                if ($site->getRootId() === $document->getId()) {
                    $siteIsLanguageRoot = TRUE;
                }

                $hostUrl = \Pimcore\Tool::getHostUrl();
            } else {
                /** @var \Pimcore\Model\Site $documentSite */
                $documentSite = \Pimcore\Tool\Frontend::getSiteForDocument($document);

                if ($documentSite->getRootId() === $document->getId()) {
                    $siteIsLanguageRoot = TRUE;
                }

                $hostUrl = \Pimcore\Tool::getRequestScheme() . '://' . $documentSite->getMainDomain();
            }

            $url = rtrim($hostUrl, '/');
        }

        if ($returnAsArray === TRUE) {
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
     * @param \Pimcore\Model\Document $document
     *
     * @return bool
     */
    private function isDocumentInCurrentSite($document)
    {
        if (!\Pimcore\Model\Site::isSiteRequest()) {
            return FALSE;
        }

        $site = \Pimcore\Model\Site::getCurrentSite();

        if (\Pimcore\Tool\Frontend::isDocumentInCurrentSite($document) || $site->getRootId() === $document->getId()) {
            return TRUE;
        }

        return FALSE;
    }
}