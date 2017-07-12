<?php

namespace I18nBundle\Adapter\Country;

use CoreShop\Model\Country;

class CoreShop extends AbstractCountry
{
    /**
     * @return array
     */
    public function getActiveCountries() : array
    {
        $list = Country::getActiveCountries();

        $cList = [
            'GLOBAL' => self::getGlobalInfo()
        ];

        /** @var Country $c */
        foreach ($list as $c) {
            $cList[$c->getIsoCode()] = [
                'isoCode' => $c->getIsoCode(),
                'id'      => $c->getId(),
                'object'  => $c
            ];
        }

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
        $data = NULL;

        //no info for global!
        if ($isoCode === 'GLOBAL') {
            return $data;
        }

        $country = Country::getByIsoCode($isoCode);
        if ($country instanceof Country) {
            if (is_null($field)) {
                $data = $country;
            } else {
                $method = 'get' . ucfirst($field);
                $data = $country->$method();
            }
        }

        return $data;
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