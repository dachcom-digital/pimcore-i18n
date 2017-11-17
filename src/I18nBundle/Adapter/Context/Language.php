<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;

class Language extends AbstractContext
{
    /**
     * Helper: Get current Language Info
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
     * Helper: Get all active languages
     *
     * @return array
     */
    public function getActiveLanguages()
    {
        $languages = [];
        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($tree as $domainElement) {
            if(empty($domainElement['languageIso'])) {
                continue;
            }

            $languageData = $this->mapLanguageInfo($domainElement['languageIso'], NULL, $domainElement['url']);
            $languageData['linkedHref'] = $domainElement['url'];
            foreach($linkedLanguages as $linkedLanguage) {
                if($linkedLanguage['languageIso'] === $domainElement['languageIso']) {
                    $languageData['linkedHref'] = $linkedLanguage['url'];
                    break;
                }
            }
            $languages[] = $languageData;
        }

        return $languages;
    }
}