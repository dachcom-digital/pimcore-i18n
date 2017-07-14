<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;

class Language extends AbstractContext
{
    /**
     * @param $field
     *
     * @return string
     */
    public function getCurrentLanguageInfo($field = 'name')
    {
        $languageData = NULL;

        if (Cache\Runtime::isRegistered('i18n.languageIso')) {
            $countryIso = Cache\Runtime::get('i18n.languageIso');
            $languageData = $this->zoneManager->getCurrentZoneLanguageAdapter()->getLanguageData($countryIso, $field);
        }

        return $languageData;
    }

    /**
     * @return array
     */
    public function getActiveLanguages()
    {
        $languages = [];
        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);

        foreach ($tree as $domainElement) {
            if(empty($domainElement['languageIso'])) {
                continue;
            }
            $languages[] = $this->mapLanguageInfo($domainElement['languageIso'], NULL, $domainElement['url']);
        }

        return $languages;
    }

    /**
     * @param bool $onlyShowRootLanguages
     *
     * @return array
     */
    public function getLinkedLanguages($onlyShowRootLanguages = FALSE)
    {
        $currentDocument = $this->getDocument();
        $urls = $this->pathGeneratorManager->getPathGenerator()->getUrls($currentDocument, $onlyShowRootLanguages);
        return $urls;
    }

}