<?php

namespace I18nBundle\Adapter\Locale;

use I18nBundle\Definitions;
use Pimcore\Config;
use Pimcore\Tool;

class System extends AbstractLocale
{
    protected array $validLocales = [];
    protected ?string $defaultLocale = null;

    public function getDefaultLocale(): ?string
    {
        if ($this->defaultLocale !== null) {
            return $this->defaultLocale;
        }

        $configDefaultLocale = $this->currentZoneConfig['default_locale'];

        if (!is_null($configDefaultLocale)) {
            $defaultLocale = $configDefaultLocale;
        } else {
            $config = Config::getSystemConfiguration('general');
            $defaultSystemLocale = $config['default_language'];
            $defaultLocale = $defaultSystemLocale;
        }

        $this->defaultLocale = $defaultLocale;

        return $this->defaultLocale;
    }

    public function getActiveLocales(): array
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

    public function getLocaleData($locale, $field = null, $keyIdentifier = 'locale'): mixed
    {
        $key = array_search($locale, array_column($this->getActiveLocales(), $keyIdentifier), true);
        if ($key !== false) {
            return $this->validLocales[$key][$field];
        }

        return null;
    }

    public function getGlobalInfo(): array
    {
        return [
            'id'      => null,
            'locale'  => null,
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE
        ];
    }
}
