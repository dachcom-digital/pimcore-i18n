<?php

namespace I18nBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use I18nBundle\Configuration\Configuration;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190224152059 extends AbstractPimcoreMigration
{
    /**
     * @return bool
     */
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(Configuration::SYSTEM_CONFIG_DIR_PATH . '/config.yml')) {
            $fileSystem->remove(Configuration::SYSTEM_CONFIG_DIR_PATH . '/config.yml');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // nothing to do.
    }
}
