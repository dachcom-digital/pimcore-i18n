<?php

namespace I18nBundle\Adapter\Country;

class Flat extends AbstractCountry
{
    /**
     * @return array
     */
    public function getActiveCountries(): array
    {
        $cList = [$this->getGlobalInfo()];
        return $cList;
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