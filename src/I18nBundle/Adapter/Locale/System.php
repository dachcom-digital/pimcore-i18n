<?php

namespace I18nBundle\Adapter\Locale;

use I18nBundle\Definitions;
use Pimcore\Config;
use Pimcore\Tool;

class System extends AbstractLocale
{
    /**
     * @var null
     */
    protected $validLocales = null;

    /**
     * @var bool|null|string
     */
    protected $defaultLocale = false;

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        if ($this->defaultLocale !== false) {
            return $this->defaultLocale;
        }

        $defaultLocale = null;
        $configDefaultLocale = $this->currentZoneConfig['default_locale'];

        if (!is_null($configDefaultLocale)) {
            $defaultLocale = $configDefaultLocale;
        } else {
            $config = Config::getSystemConfig();
            $defaultSystemLocale = $config->general->defaultLanguage;
            $defaultLocale = $defaultSystemLocale;
        }

        $this->defaultLocale = $defaultLocale;

        return $this->defaultLocale;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getLocaleData($locale, $field = null, $keyIdentifier = 'locale')
    {
        $key = array_search($locale, array_column($this->validLocales, $keyIdentifier));
        if ($key !== false) {
            return $this->validLocales[$key][$field];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalInfo()
    {
        return [
            'id'      => null,
            'locale'  => null,
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE
        ];
    }
}
