<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\CookieHelper;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CookieRedirector extends AbstractRedirector
{
    protected CookieHelper $cookieHelper;

    public function __construct()
    {
        $this->cookieHelper = new CookieHelper($this->config['cookie']);
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

    protected function getConfigResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired('cookie');
        $resolver->setDefault('cookie', function(OptionsResolver $cookieResolver) {
            $cookieResolver
                ->setRequired(['path', 'domain', 'secure', 'http_only', 'same_site'])
                ->setDefaults([
                    'path' => '/',
                    'domain' => null,
                    'secure' => false,
                    'http_only' => true,
                    'same_site' => Cookie::SAMESITE_LAX,
                    'expire' => '+1 year'
                ])
                ->setAllowedTypes('path', 'string')
                ->setAllowedTypes('domain', ['string', 'null'])
                ->setAllowedTypes('secure', 'bool')
                ->setAllowedTypes('http_only', 'bool')
                ->setAllowedTypes('same_site', 'string')
                ->setAllowedTypes('expire', ['integer', 'string'])
                ->setAllowedValues('same_site', [Cookie::SAMESITE_LAX, Cookie::SAMESITE_STRICT, Cookie::SAMESITE_NONE]);
        });

        return $resolver;
    }
}
