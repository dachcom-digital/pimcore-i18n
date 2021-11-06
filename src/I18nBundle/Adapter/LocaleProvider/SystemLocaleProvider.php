<?php

namespace I18nBundle\Adapter\LocaleProvider;

use I18nBundle\Definitions;
use Pimcore\Config;
use Pimcore\Tool;

class SystemLocaleProvider extends AbstractLocaleProvider
{
    protected array $validLocales = [];

    public function getDefaultLocale(array $zoneDefinition): ?string
    {
        $configDefaultLocale = $zoneDefinition['default_locale'];

        if (!is_null($configDefaultLocale)) {
            $defaultLocale = $configDefaultLocale;
        } else {
            $config = Config::getSystemConfiguration('general');
            $defaultSystemLocale = $config['default_language'];
            $defaultLocale = $defaultSystemLocale;
        }

        return $defaultLocale;
    }

    public function getActiveLocales(array $zoneDefinition): array
    {
        if (!empty($this->validLocales)) {
            return $this->validLocales;
        }

        $validLocales = [];
        $systemLocales = Tool::getValidLanguages();

        foreach ($systemLocales as $id => $locale) {
            $validLocales[] = [
                'id'      => (int) $id,
                'locale'  => $locale,
                'isoCode' => $locale
            ];
        }

        $this->validLocales = $validLocales;

        return $this->validLocales;
    }

    public function getLocaleData(array $zoneDefinition, string $locale, string $field, string $keyIdentifier = 'locale'): mixed
    {
        $key = array_search($locale, array_column($this->getActiveLocales($zoneDefinition), $keyIdentifier), true);

        if ($key !== false) {
            return $this->validLocales[$key][$field];
        }

        return null;
    }

    public function getGlobalInfo(array $zoneDefinition): array
    {
        return [
            'id'      => null,
            'locale'  => null,
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE
        ];
    }
}
