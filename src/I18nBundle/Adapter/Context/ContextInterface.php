<?php

namespace I18nBundle\Adapter\Context;

interface ContextInterface
{
    public function getLinkedLanguages($onlyShowRootLanguages = true);

    public function getCurrentContextInfo($slot = null);

    public function getCurrentLocale();

    public function getCurrentLanguageIso();
}