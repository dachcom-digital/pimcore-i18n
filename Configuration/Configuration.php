<?php

namespace I18nBundle\Configuration;

class Configuration
{
    const SYSTEM_CONFIG_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/I18nBundle/config.yml';

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $systemConfig;

    protected $countryAdapter;

    public function __construct($countryAdapter)
    {
        $this->countryAdapter = $countryAdapter;
    }

    /**
     * @param array $config
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
    }

    /**
     * @param $slot
     *
     * @return mixed
     */
    public function getConfig($slot)
    {
        return $this->config[$slot];
    }

    /**
     * @param array $config
     */
    public function setSystemConfig($config = [])
    {
        $this->systemConfig = $config;
    }

    /**
     * @param null $slot
     *
     * @return mixed
     */
    public function getSystemConfig($slot = NULL)
    {
        return $this->systemConfig[$slot];
    }

    /**
     * @return mixed
     */
    public function getCountryAdapter()
    {
        return $this->countryAdapter;
    }
}