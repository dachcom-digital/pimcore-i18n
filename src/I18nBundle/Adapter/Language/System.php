<?php

namespace I18nBundle\Adapter\Language;

use Pimcore\Tool;
use Pimcore\Config;

class System extends AbstractLanguage
{
    /**
     * @var null
     */
    var $validLanguages = NULL;

    /**
     * @var null
     */
    var $defaultLanguage = NULL;

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
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        if (!is_null($this->defaultLanguage)) {
            return $this->defaultLanguage;
        }

        $config = Config::getSystemConfig();
        $this->defaultLanguage = $config->general->defaultLanguage;

        return $this->defaultLanguage;
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
}