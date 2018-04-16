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
     * returns valid locale
     *
     * @return bool|mixed|null|string
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
     * @return array
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
                'id'      => (int)$id,
                'locale'  => $locale,
                'isoCode' => $locale
            ];
        }

        $this->validLocales = $validLocales;

        return $this->validLocales;
    }

    /**
     * @param string $locale
     * @param null   $field
     * @param string $keyIdentifier
     *
     * @return mixed
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
     * @return array
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