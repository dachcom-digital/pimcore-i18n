<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Model\Document;

interface ContextInterface
{
    public function setDocument(Document $document);

    public function getDocument();

    public function getCurrentLocale();

    public function getCurrentLanguageIso();

    public function getCurrentCountryIso();

    public function getLinkedLanguages($onlyShowRootLanguages = true);

    public function getLanguageNameByIsoCode($languageIso, $locale = null, $region = null);

    public function getCurrentContextInfo($slot = null);
}
