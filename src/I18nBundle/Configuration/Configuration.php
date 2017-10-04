<?php

namespace I18nBundle\Configuration;

class Configuration
{
    const SYSTEM_CONFIG_DIR_PATH = PIMCORE_PRIVATE_VAR . '/bundles/I18nBundle';

    const SYSTEM_CONFIG_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/I18nBundle/config.yml';

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $systemConfig;

    /**
     * @param array $config
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
    }
    /**
     * @return array
     */
    public function getConfigNode()
    {
        return $this->config;
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
}