<?php

namespace I18nBundle\Adapter\Country;

use I18nBundle\Definitions;
use Pimcore\Config;
use Pimcore\Tool;

class System extends AbstractCountry
{
    /**
     * @var null
     */
    protected $validCountries = NULL;

    /**
     * @var bool|null|string
     */
    protected $defaultCountry = FALSE;

    /**
     * @return array
     */
    public function getActiveCountries(): array
    {
        if (!empty($this->validCountries)) {
            return $this->validCountries;
        }

        $validCountries = [$this->getGlobalInfo()];
        foreach (Tool::getValidLanguages() as $id => $language) {

            if (strpos($language, '_') === FALSE) {
                continue;
            }

            $parts = explode('_', $language);

            $validCountries[] = [
                'isoCode' => $parts[1],
                'id'      => $id,
                'zone'    => NULL,
                'object'  => NULL
            ];
        }

        $this->validCountries = $validCountries;

        return $this->validCountries;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return null
     */
    public function getCountryData($isoCode = '', $field = NULL)
    {
        $key = array_search($isoCode, array_column($this->validCountries, 'isoCode'));
        if ($key !== FALSE) {
            return $this->validCountries[$key][$field];
        }

        return NULL;
    }

    /**
     * @return string|null
     */
    public function getDefaultCountry()
    {
        if ($this->defaultCountry !== FALSE) {
            return $this->defaultCountry;
        }

        $defaultCountry = NULL;
        $configDefaultCountry = $this->currentZoneConfig['default_country'];

        if(!is_null($configDefaultCountry)) {
            $defaultCountry = $configDefaultCountry;
        } else {
            $config = Config::getSystemConfig();
            $defaultLanguage = $config->general->defaultLanguage;
            if (strpos($defaultLanguage, '_') === FALSE) {
                $defaultCountry = $this->getGlobalInfo()['isoCode'];
            } else {
                $defaultCountry = end(explode('_', $defaultLanguage));
            }
        }

        $this->defaultCountry = $defaultCountry;

        return $this->defaultCountry;
    }

    /**
     * @return array
     */
    public function getGlobalInfo()
    {
        return [

            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE,
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL

        ];
    }
}