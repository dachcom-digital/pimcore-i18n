<?php

namespace I18nBundle\Adapter\Locale;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AcceptLanguageHeader extends System
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getDefaultLocale()
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request instanceof Request) {
            return parent::getDefaultLocale();
        }

        $acceptLanguages = $request->getLanguages();
        if ($acceptLanguages) {
            foreach ($acceptLanguages as $acceptLanguage) {
                if ($this->getLocaleData($acceptLanguage, 'locale') !== null) {
                    return $acceptLanguage;
                }
            }
        }

        return parent::getDefaultLocale();
    }
}