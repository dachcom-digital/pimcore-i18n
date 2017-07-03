<?php

namespace I18nBundle\Country\Adapter;

use I18nBundle\Country\CountryInterface;

class Website implements CountryInterface
{
    var $countries = [
        'AT'     => [
            'name'    => 'Austria',
            'isoCode' => 'AT',
            'id'      => 1,
            'zone'    => null,
            'object'  => null
        ],
        'DE'     => [
            'name'    => 'Germany',
            'isoCode' => 'DE',
            'id'      => 2,
            'zone'    => null,
            'object'  => null
        ],
        'CH'     => [
            'name'    => 'Switzerland',
            'isoCode' => 'CH',
            'id'      => 3,
            'zone'    => null,
            'object'  => null
        ],
        'US'     => [
            'name'    => 'United States',
            'isoCode' => 'US',
            'id'      => 4,
            'zone'    => null,
            'object'  => null
        ],
        'GLOBAL' => [
            'name'    => 'global',
            'isoCode' => 'GLOBAL',
            'id'      => null,
            'zone'    => null,
            'object'  => null
        ]
    ];

    /**
     * @return array
     */
    public function getActiveCountries()
    {
        return $this->countries;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return null
     */
    public function getCountryData($isoCode = '', $field = NULL)
    {
        $config = $this->countries;

        //no info for global!
        if ($isoCode === 'GLOBAL') {
            return NULL;
        }

        if (isset($config[$isoCode])) {
            if ($field && isset($config[$isoCode][$field])) {
                return $config[$isoCode][$field];
            }

            return $config[$isoCode];
        }

        return NULL;
    }

    /**
     * @return mixed
     */
    public function getGlobalInfo()
    {
        return $this->countries['GLOBAL'];
    }
}