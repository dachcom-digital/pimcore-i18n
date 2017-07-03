<?php

namespace I18nBundle\Tool;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;

use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;
use I18nBundle\Configuration\Configuration;

class Install extends AbstractInstaller
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $installSourcesPath;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->installSourcesPath = __DIR__ . '/../Resources/install';
        $this->fileSystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->copyConfigFiles();
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


}