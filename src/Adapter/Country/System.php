<?php

namespace I18nBundle\Adapter\Country;

use Pimcore\Tool;

class System extends AbstractCountry
{
    /**
     * @return array
     */
    public function getActiveCountries(): array
    {
        $validCountries = [$this->getGlobalInfo()];

        foreach (Tool::getValidLanguages() as $id => $language) {

            if(strpos($language, '_') === FALSE) {
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

        return $validCountries;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return null
     */
    public function getCountryData($isoCode = '', $field = NULL)
    {
        return NULL;
    }

    /**
     * @return array
     */
    public function getGlobalInfo()
    {
        return [

            'isoCode' => 'GLOBAL',
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL

        ];
    }
}