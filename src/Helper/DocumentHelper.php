<?php

namespace I18nBundle\Helper;

use Pimcore\Model\Site as PimcoreSite;

class DocumentHelper
{
    /**
     * Get All root connected documents (connected via translations)
     * @todo: add caching key?
     * @return array
     */
    public function getRootConnectedDocuments()
    {
        $docs = [];
        $rootPath = $this->getCurrentPageRootPath();
        $translations = NULL;
        $connector = NULL;

        $singlePage = FALSE;
        $singlePageId = NULL;

        $currentRootDoc = \Pimcore\Model\Document::getByPath($rootPath);

        // no language tag found. so current root doc does have language based childs!
        // get at least one of them to get connected languages!
        if (empty($currentRootDoc->getProperty('language'))) {

            $db = \Pimcore\Db::get();
            $select = $db->select();
            $select->from(['document' => 'documents'], [new \Pimcore\Db\ZendCompatibility\Expression('COUNT(document.id) as count'), 'document.id']);
            $select->where('document.path LIKE ?', $rootPath);
            $select->where('document.parentId = ?', $currentRootDoc->getId());
            $select->where('document.published = ?', 1);

            $row = $db->fetchRow($select);

            //there is just one subpage, maybe just one language?
            if ((int)$row['count'] === 1) {
                $singlePage = TRUE;
                $singlePageId = (int)$row['id'];
                //there are more. just use the current row id and check the translation references.
            } else {
                $connector = \Pimcore\Model\Document::getById($row['id']);
            }
        } else {
            $connector = $currentRootDoc;
        }

        if ($singlePage === TRUE) {
            $translations[] = $singlePageId;
        } else if ($connector instanceof \Pimcore\Model\Document) {
            $service = new \Pimcore\Model\Document\Service;
            $translations = $service->getTranslations($connector);
        }

        if (is_array($translations)) {
            foreach ($translations as $langIso => $docId) {
                $document = \Pimcore\Model\Document::getById($docId);

                if (!$document->isPublished()) {
                    continue;
                }

                $documentUrlInfo = $this->getDocumentUrl($document, TRUE, FALSE);

                $homeUrl = $documentUrlInfo['url'];
                $langIso = $document->getProperty('language');
                $countryIso = $document->getProperty('country');

                if ($documentUrlInfo['siteIsLanguageRoot'] === FALSE) {
                    $homeUrl = \I18nBundle\Tool\System::joinPath([$homeUrl, $langIso]);
                }

                $docs[] = [
                    'siteIsLanguageRoot' => $documentUrlInfo['siteIsLanguageRoot'],
                    'hostUrl'            => $documentUrlInfo['url'],
                    'homeUrl'            => $homeUrl,
                    'realFullPath'       => $document->getRealFullPath(),
                    'langIso'            => $langIso,
                    'countryIso'         => $countryIso
                ];
            }
        }

        return $docs;
    }

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