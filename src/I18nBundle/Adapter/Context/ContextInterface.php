<?php

namespace I18nBundle\Adapter\Context;

interface ContextInterface
{
    public function getLinkedLanguages($onlyShowRootLanguages = true);

    public function getCurrentContextInfo($slot = null, $locale = null);

    public function getCurrentLanguageIso();

    public function getCurrentCountryIso();
}