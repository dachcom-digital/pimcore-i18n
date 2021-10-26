<?php

namespace I18nBundle\Builder;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;
use I18nBundle\Definitions;
use I18nBundle\Model\I18nContext;
use I18nBundle\Model\I18nContextInterface;

class ContextBuilder
{
    public function build(string $baseLocale, LocaleProviderInterface $localeProvider, string $mode): I18nContextInterface
    {
        $activeZoneLocales = $localeProvider->getActiveLocales();

        return $this->buildSourceData($baseLocale, $mode, $activeZoneLocales);
    }

    protected function buildSourceData(string $baseLocale, string $mode, array $activeZoneLocales): I18nContextInterface
    {
        $locale = $baseLocale === '' ? null : $baseLocale;
        $languageIso = $locale;
        $countryIso = null;

        if ($mode === 'country') {
            $countryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        if (str_contains($baseLocale, '_')) {
            $parts = explode('_', $baseLocale);
            $languageIso = strtolower($parts[0]);
            if (isset($parts[1]) && !empty($parts[1])) {
                $countryIso = strtoupper($parts[1]);
            }
        }

        return new I18nContext(
            $locale,
            $languageIso,
            $countryIso,
            $this->validateZoneLocale($baseLocale, $activeZoneLocales)
        );
    }

    protected function validateZoneLocale(?string $locale, array $activeZoneLocales): bool
    {
        if ($locale === null) {
            return false;
        }

        return in_array($locale, array_column($activeZoneLocales, 'locale'), true);
    }
}
