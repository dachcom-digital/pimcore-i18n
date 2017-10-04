<?php

namespace I18nBundle\Tool;

use I18nBundle\I18nBundle;
use Pimcore\Config;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;

use Symfony\Component\Filesystem\Filesystem;
use I18nBundle\Configuration\Configuration;
use Pimcore\Model\Property;
use Symfony\Component\Yaml\Yaml;

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
        $this->installOrUpdateConfigFile();
        $this->installProperties();
    }

    /**
     * For now, just update the config file to the current version.
     * {@inheritdoc}
     */
    public function update()
    {
        $this->installOrUpdateConfigFile();
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
        $needUpdate = FALSE;
        if ($this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $config = Yaml::parse(file_get_contents(Configuration::SYSTEM_CONFIG_FILE_PATH));
            if($config['version'] !== I18nBundle::BUNDLE_VERSION) {
                $needUpdate = TRUE;
            }
        }

        return $needUpdate;
    }

    /**
     * install config file
     */
    private function installOrUpdateConfigFile()
    {
        if(!$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_DIR_PATH)) {
            $this->fileSystem->mkdir(Configuration::SYSTEM_CONFIG_DIR_PATH);
        }

        $config = ['version' => I18nBundle::BUNDLE_VERSION];
        $yml = Yaml::dump($config);
        file_put_contents(Configuration::SYSTEM_CONFIG_FILE_PATH, $yml);
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
                'name'        => 'I18n: Country Context',
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