<?php

namespace I18nBundle\Tool;

use Pimcore\Config;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;

use Symfony\Component\Filesystem\Filesystem;
use I18nBundle\Configuration\Configuration;
use Pimcore\Model\Property;

class Install extends AbstractInstaller
{
    /**
     * @var string
     */
    private $installSourcesPath;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * Install constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->installSourcesPath = __DIR__ . '/../Resources/install';
        $this->fileSystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->copyConfigFiles();
        $this->installProperties();

        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        if ($this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $this->fileSystem->rename(
                Configuration::SYSTEM_CONFIG_FILE_PATH,
                PIMCORE_PRIVATE_VAR . '/bundles/I18nBundle/config_backup.yml'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return !$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUpdated()
    {
        return FALSE;
    }

    /**
     * copy sample config file - if not exists.
     */
    private function copyConfigFiles()
    {
        if (!$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $this->fileSystem->copy(
                $this->installSourcesPath . '/config.yml',
                Configuration::SYSTEM_CONFIG_FILE_PATH
            );
        }
    }

    /**
     * @return bool
     */
    private function installProperties()
    {
        $countries = ['GLOBAL'];

        $config = Config::getSystemConfig();
        $languages = explode(',', $config->general->validLanguages);
        foreach ($languages as $language) {
            if (strpos($language, '_') !== FALSE) {
                $countries[] = end(explode('_', $language));
            }
        }

        $properties = [
            'front_page_map' => [
                'ctype'       => 'document',
                'type'        => 'document',
                'config'      => '',
                'name'        => 'I18n: Front Page Mapping',
                'description' => 'I18n: Use this property to map a custom front page.'
            ],
            'country'        => [
                'ctype'       => 'document',
                'type'        => 'select',
                'config'      => join(',', $countries),
                'name'        => 'I18n: Define Country Context',
                'description' => ''
            ]
        ];

        foreach ($properties as $key => $propertyConfig) {
            $defProperty = Property\Predefined::getByKey($key);

            if ($defProperty instanceof Property\Predefined) {
                continue;
            }

            $property = new Property\Predefined();
            $property->setKey($key);
            $property->setType($propertyConfig['type']);
            $property->setName($propertyConfig['name']);

            $property->setDescription($propertyConfig['description']);
            $property->setCtype($propertyConfig['ctype']);
            $property->setConfig($propertyConfig['config']);
            $property->setInheritable(FALSE);
            $property->save();
        }

        return TRUE;
    }

}