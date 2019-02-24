<?php

namespace I18nBundle\Tool;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\Version;
use I18nBundle\Configuration\Configuration;
use Pimcore\Model\Property;
use Pimcore\Model\Translation;
use Pimcore\Tool\Admin;
use Symfony\Component\Filesystem\Filesystem;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Migrations\Migration\InstallMigration;

class Install extends MigrationInstaller
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return '00000001';
    }

    /**
     * @throws AbortMigrationException
     * @throws MigrationException
     */
    protected function beforeInstallMigration()
    {
        $markVersionsAsMigrated = true;

        // legacy:
        //   we switched from config to migration
        //   if config.yml exists, this instance needs to migrate
        //   so every migration needs to run.
        // fresh:
        //   skip all versions since they are not required anymore
        //   (fresh installation does not require any version migrations)
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(Configuration::SYSTEM_CONFIG_DIR_PATH . '/config.yml')) {
            $markVersionsAsMigrated = false;
        }

        if ($markVersionsAsMigrated === true) {
            $migrationConfiguration = $this->migrationManager->getBundleConfiguration($this->bundle);
            $this->migrationManager->markVersionAsMigrated($migrationConfiguration->getVersion($migrationConfiguration->getLatestVersion()));
        }

        $this->initializeFreshSetup();
    }

    /**
     * @param Schema  $schema
     * @param Version $version
     */
    public function migrateInstall(Schema $schema, Version $version)
    {
        /** @var InstallMigration $migration */
        $migration = $version->getMigration();
        if ($migration->isDryRun()) {
            $this->outputWriter->write('<fg=cyan>DRY-RUN:</> Skipping installation');

            return;
        }
    }

    /**
     * @param Schema  $schema
     * @param Version $version
     */
    public function migrateUninstall(Schema $schema, Version $version)
    {
        /** @var InstallMigration $migration */
        $migration = $version->getMigration();
        if ($migration->isDryRun()) {
            $this->outputWriter->write('<fg=cyan>DRY-RUN:</> Skipping uninstallation');

            return;
        }

        // currently nothing to do.
    }

    /**
     * @param string|null $version
     *
     * @throws AbortMigrationException
     */
    protected function beforeUpdateMigration(string $version = null)
    {
        $this->installTranslations();
    }

    /**
     * @throws AbortMigrationException
     */
    public function initializeFreshSetup()
    {
        $this->installTranslations();
        $this->installProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return false;
    }

    /**
     * @return bool
     * @throws AbortMigrationException
     */
    private function installProperties()
    {
        $properties = [
            'front_page_map' => [
                'ctype'       => 'document',
                'type'        => 'document',
                'config'      => '',
                'name'        => 'I18n: Front Page Mapping',
                'description' => 'I18n: Use this property to map a custom front page.'
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
            $property->setInheritable(false);

            try {
                $property->getDao()->save();
            } catch (\Exception $e) {
                throw new AbortMigrationException(sprintf('Failed to save document proprety "%s". Error was: "%s"', $propertyConfig['name'], $e->getMessage()));
            }
        }

        return true;
    }

    /**
     * @return bool
     * @throws AbortMigrationException
     */
    private function installTranslations()
    {
        $csvAdmin = $this->getInstallSourcesPath() . '/translations/admin.csv';

        try {
            Translation\Admin::importTranslationsFromFile($csvAdmin, true, Admin::getLanguages());
        } catch (\Exception $e) {
            throw new AbortMigrationException(sprintf('Failed to install admin translations. error was: "%s"', $e->getMessage()));
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getInstallSourcesPath()
    {
        return __DIR__ . '/../Resources/install';
    }
}
