<?php

namespace I18nBundle\Adapter\Language;

use Pimcore\Tool;
use Pimcore\Config;

class System extends AbstractLanguage
{
    /**
     * @var null
     */
    var $defaultLanguage = NULL;

    /**
     * @return array
     */
    public function getActiveLanguages(): array
    {
        $validLanguages = [];
        $languages = Tool::getValidLanguages();

        foreach ($languages as $id => $language) {
            $validLanguages[] = [
                'id'      => (int)$id,
                'isoCode' => $language
            ];
        }

        return $validLanguages;
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
     * @return null
     */
    public function getLanguageData($isoCode = '', $field = NULL)
    {
        return NULL;
    }
}