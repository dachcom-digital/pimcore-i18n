<?php

namespace I18nBundle\Adapter\Context;

interface ContextInterface
{
    public function getLinkedLanguages($onlyShowRootLanguages = TRUE);

    public function getCurrentContextInfo($slot = NULL, $locale = NULL);

    public function getCurrentLanguageIso();

    public function getCurrentCountryIso();
}