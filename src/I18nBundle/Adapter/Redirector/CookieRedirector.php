<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\CookieHelper;

class CookieRedirector extends AbstractRedirector
{
    protected CookieHelper $cookieHelper;

    public function __construct(CookieHelper $cookieHelper)
    {
        $this->cookieHelper = $cookieHelper;
    }

    public function makeDecision(RedirectorBag $redirectorBag): void
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        $valid = false;
        $url = null;
        $locale = null;
        $country = null;
        $language = null;

        $request = $redirectorBag->getRequest();
        $redirectCookie = $this->cookieHelper->get($request);

        //if no cookie available the validation fails.
        if (is_array($redirectCookie) && !empty($redirectCookie['url'])) {
            $valid = true;
            $url = $redirectCookie['url'];
            $locale = $redirectCookie['locale'];
            $country = $redirectCookie['country'];
            $language = $redirectCookie['language'];
        }

        $this->setDecision([
            'valid'    => $valid,
            'locale'   => is_string($locale) ? $locale : null,
            'country'  => is_string($country) ? $country : null,
            'language' => is_string($language) ? $language : null,
            'url'      => $url
        ]);
    }
}
