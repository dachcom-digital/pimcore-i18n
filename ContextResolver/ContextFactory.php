<?php

namespace I18nBundle\ContextResolver;

use I18nBundle\ContextResolver\Context\AbstractContext;
use I18nBundle\ContextResolver\Context\Country;
use I18nBundle\ContextResolver\Context\Language;

class ContextFactory {

    protected static $instance;

    /**
     * @param null $i18nType
     *
     * @return Context\AbstractContext
     * @throws \Exception
     */
    public static function get($i18nType = NULL)
    {
        if(self::$instance instanceof AbstractContext) {
            return self::$instance;
        }

        switch ($i18nType) {

            case 'language':
                self::$instance = new Language();
                break;

            case 'country':
                self::$instance = new Country();
                break;

            default:
                throw new \Exception($i18nType . ' not found.');
        }

        return self::$instance;
    }

}