<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;

class Language extends AbstractContext
{
    /**
     * Helper: Get current Language Info.
     *
     * @param string $field
     *
     * @return string
     */
    public function getCurrentLanguageInfo($field = 'name')
    {
        $languageData = null;
        if (!Cache\Runtime::isRegistered('i18n.locale')) {
            return null;
        }

        try {
            $locale = Cache\Runtime::get('i18n.locale');
            $languageData = $this->zoneManager->getCurrentZoneLocaleAdapter()->getLocaleData($locale, $field);
        } catch (\Exception $e) {
            return null;
        }

        return $languageData;
    }

    /**
     * Helper: Get all active languages.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getActiveLanguages()
    {
        $languages = [];
        $tree = $this->zoneManager->getCurrentZoneDomains(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($tree as $domainElement) {
            if (empty($domainElement['languageIso'])) {
                continue;
            }

            $languageData = $this->mapLanguageInfo($domainElement['locale'], $domainElement['url']);
            $languageData['linkedHref'] = $domainElement['url'];
            $languageData['active'] = $domainElement['languageIso'] === $this->getCurrentLanguageIso();
            foreach ($linkedLanguages as $linkedLanguage) {
                if ($linkedLanguage['languageIso'] === $domainElement['languageIso']) {
                    $languageData['linkedHref'] = $linkedLanguage['url'];

                    break;
                }
            }
            $languages[] = $languageData;
        }

        return $languages;
    }
}
