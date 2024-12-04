<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\CookieHelper;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CookieRedirector extends AbstractRedirector
{
    public function makeDecision(RedirectorBag $redirectorBag): void
    {
        $cookieHelper = new CookieHelper($this->config['cookie']);

        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        $valid = false;
        $url = null;
        $locale = null;
        $country = null;
        $language = null;

        $request = $redirectorBag->getRequest();
        $redirectCookie = $cookieHelper->get($request);

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
        $resolver->setDefault('cookie', function (OptionsResolver $cookieResolver) {
            $cookieResolver
                ->setRequired(['path', 'domain', 'secure', 'http_only', 'same_site'])
                ->setDefaults([
                    'path'      => '/',
                    'domain'    => null,
                    'secure'    => false,
                    'http_only' => true,
                    'same_site' => Cookie::SAMESITE_LAX,
                    'expire'    => '+1 year'
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
