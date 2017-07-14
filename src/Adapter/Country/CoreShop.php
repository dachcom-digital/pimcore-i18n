<?php

namespace I18nBundle\Adapter\Country;

use CoreShop\Model\Country;
use I18nBundle\Definitions;

class CoreShop extends AbstractCountry
{
    /**
     * @return array
     */
    public function getActiveCountries() : array
    {
        $list = Country::getActiveCountries();

        $cList = [
            Definitions::INTERNATIONAL_COUNTRY_NAMESPACE => self::getGlobalInfo()
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
        if ($isoCode === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
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
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE,
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL

        ];
    }
}