<?php

namespace I18nBundle\Adapter\Country;

use I18nBundle\Definitions;
use Pimcore\Tool;

class System extends AbstractCountry
{
    /**
     * @var null
     */
    protected $validCountries = NULL;

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