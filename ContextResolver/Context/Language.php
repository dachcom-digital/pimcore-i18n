<?php

namespace I18nBundle\ContextResolver\Context;

use Pimcore\Cache;
use Symfony\Component\Intl\Intl;

class Language extends AbstractContext
{
    /**
     * @return string
     */
    public function getCurrentLanguage()
    {
        if (Cache\Runtime::isRegistered('i18n.langIso')) {
            $isoCode = Cache\Runtime::get('i18n.langIso');
            return $isoCode;
        }

        return FALSE;
    }

    /**
     * @return array|bool
     */
    public function getActiveLanguages()
    {
        $validLanguages = \Pimcore\Tool::getValidLanguages();

        $languages = [];

        $rootConnectedDocuments = $this->relationHelper->getRootConnectedDocuments();

        foreach ($rootConnectedDocuments as $doc) {
            if (in_array($doc['langIso'], $validLanguages)) {
                $languages[] = $this->mapLanguageInfo($doc['langIso'], $doc['homeUrl']);
            }
        }

        return $languages;
    }

    /**
     * @param bool $onlyShowRootLanguages
     * @param bool $strictMode if false and document couldn't be found, the language root page will be shown
     *                         Mostly used for navigation drop downs or lists.
     *                         Get all linked documents from given document in current country!
     *
     * @return array|bool|mixed
     */
    public function getLinkedLanguages($onlyShowRootLanguages = TRUE, $strictMode = FALSE)
    {
        $activeLanguages = $this->getActiveLanguages();

        if ($onlyShowRootLanguages === TRUE) {
            return $activeLanguages;
        } else {

            $currentDocument = $this->getDocument();
            $urls = $this->getPathResolver()->getUrls($currentDocument, []);

            $validLinks = [];

            foreach ($urls as $url) {
                $validLinks[] = $this->mapLanguageInfo($url['language'], $url['href']);
            }

            //add missing languages, if strictMode is off.
            if ($strictMode === FALSE) {
                $compareArray = array_diff(
                    array_column($activeLanguages, 'iso'),
                    array_column($validLinks, 'iso')
                );

                foreach ($activeLanguages as $languageInfo) {
                    if (in_array($languageInfo['iso'], $compareArray)) {
                        $validLinks[] = $languageInfo;
                    }
                }
            }

            return $validLinks;
        }
    }

    /**
     * @param $language
     * @param $href
     *
     * @return array
     */
    public function mapLanguageInfo($language, $href)
    {
        return [

            'iso'         => $language,
            'titleNative' => Intl::getLanguageBundle()->getLanguageName($language, $language),
            'title'       => Intl::getLanguageBundle()->getLanguageName($language, $this->getCurrentLanguage()),
            'href'        => $href

        ];
    }
}