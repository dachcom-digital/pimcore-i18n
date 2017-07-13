<?php

namespace I18nBundle\Adapter\Context;

class Language extends AbstractContext
{
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