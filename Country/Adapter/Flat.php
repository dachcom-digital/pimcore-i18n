<?php

namespace I18nBundle\Country\Adapter;

use I18nBundle\Country\CountryInterface;

class Flat implements CountryInterface
{
    /**
     * @return array
     */
    public function getActiveCountries()
    {
        $cList = [
            'GLOBAL' => $this->getGlobalInfo()
        ];

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

            'name'    => 'global',
            'isoCode' => 'GLOBAL',
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL

        ];
    }
}