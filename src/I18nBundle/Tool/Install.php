<?php

namespace I18nBundle\Tool;

use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\Property;
use Pimcore\Model\Translation;
use Pimcore\Tool\Admin;

class Install extends SettingsStoreAwareInstaller
{
    public function install(): void
    {
        $this->installTranslations();
        $this->installProperties();

        parent::install();
    }

    private function installProperties(): void
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
                throw new InstallationException(sprintf('Failed to save document property "%s". Error was: "%s"', $propertyConfig['name'], $e->getMessage()));
            }
        }

    }

    private function installTranslations(): void
    {
        $csvAdmin = $this->getInstallSourcesPath() . '/translations/admin.csv';

        try {
            Translation::importTranslationsFromFile($csvAdmin, 'admin', true, Admin::getLanguages());
        } catch (\Exception $e) {
            throw new InstallationException(sprintf('Failed to install admin translations. error was: "%s"', $e->getMessage()));
        }
    }

    protected function getInstallSourcesPath(): string
    {
        return __DIR__ . '/../Resources/install';
    }
}
