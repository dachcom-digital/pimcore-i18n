<?php

namespace I18nBundle\Adapter\Language;

use Pimcore\Config;
use Pimcore\Tool;

class System extends AbstractLanguage
{
    /**
     * @var null
     */
    protected $validLanguages = NULL;

    /**
     * @var bool|null|string
     */
    protected $defaultLanguage = FALSE;

    /**
     * @return array
     */
    public function getActiveLanguages(): array
    {
        if (!empty($this->validLanguages)) {
            return $this->validLanguages;
        }

        $validLanguages = [];
        $languages = Tool::getValidLanguages();

        foreach ($languages as $id => $language) {
            $validLanguages[] = [
                'id'      => (int)$id,
                'isoCode' => $language
            ];
        }

        $this->validLanguages = $validLanguages;

        return $this->validLanguages;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return mixed
     */
    public function getLanguageData($isoCode = '', $field = NULL)
    {
        $key = array_search($isoCode, array_column($this->validLanguages, 'isoCode'));
        if ($key !== FALSE) {
            return $this->validLanguages[$key][$field];
        }

        return NULL;
    }

    /**
     * @return string|null
     */
    public function getDefaultLanguage()
    {
        if ($this->defaultLanguage !== FALSE) {
            return $this->defaultLanguage;
        }

        $defaultCountry = NULL;
        $configDefaultLanguage = $this->currentZoneConfig['default_language'];

        if(!is_null($configDefaultLanguage)) {
            $defaultLanguage = $configDefaultLanguage;
        } else {
            $config = Config::getSystemConfig();
            $defaultLanguage = $config->general->defaultLanguage;
            if(strpos($defaultLanguage, '_') !== FALSE) {
                $defaultLanguage = array_shift(explode('_', $defaultLanguage));
            }
        }

        //set to NULL if empty since pimcore returns an empty string if no default language has been defined.
        $this->defaultLanguage = empty($defaultLanguage) ? NULL : $defaultLanguage;
        return $this->defaultLanguage;
    }
}