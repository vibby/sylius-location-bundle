<?php

namespace Webburza\Sylius\LocationBundle\Command;

use Sylius\Component\Rbac\Model\Permission;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('webburza:sylius-location-bundle:install')
            ->setDescription('Installs the bundle, creates required database tables and permissions.')
            ->setHelp('Usage:  <info>$ bin/console webburza:sylius-location-bundle:install</info>')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Doctrine\ORM\EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $output->writeln('<info>Creating location tables...</info>');
        $this->createLocationTables($manager);

        $output->writeln('<info>Creating permissions...</info>');
        $this->createPermissions($manager);

        $output->writeln('<info>Installation complete.</info>');
    }

    /**
     * Create location tables.
     *
     * @param \Doctrine\ORM\EntityManager $manager
     */
    private function createLocationTables($manager)
    {
        // Check if tables already exist
        $schemaManager = $manager->getConnection()->getSchemaManager();

        // Skip if location table already exist
        if ($schemaManager->tablesExist(['webburza_sylius_location'])) {
            return;
        }

        $queries = [
            'CREATE TABLE webburza_sylius_location (id INT AUTO_INCREMENT NOT NULL, location_type INT DEFAULT NULL, published TINYINT(1) NOT NULL, internal_name VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, latitude VARCHAR(20) DEFAULT NULL, longitude VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_B50D566DCDAE269 (location_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'CREATE TABLE webburza_sylius_location_image (id INT AUTO_INCREMENT NOT NULL, location_id INT NOT NULL, path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7DC32A0A64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'CREATE TABLE webburza_sylius_location_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, street_name VARCHAR(255) DEFAULT NULL, street_number VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip VARCHAR(20) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, working_hours LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_3B2F83722C2AC5D3 (translatable_id), UNIQUE INDEX webburza_sylius_location_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'CREATE TABLE webburza_sylius_location_type (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'CREATE TABLE webburza_sylius_location_type_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_FC2A05E12C2AC5D3 (translatable_id), UNIQUE INDEX webburza_sylius_location_type_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'ALTER TABLE webburza_sylius_location ADD CONSTRAINT FK_B50D566DCDAE269 FOREIGN KEY (location_type) REFERENCES webburza_sylius_location_type (id)',
            'ALTER TABLE webburza_sylius_location_image ADD CONSTRAINT FK_7DC32A0A64D218E FOREIGN KEY (location_id) REFERENCES webburza_sylius_location (id) ON DELETE CASCADE',
            'ALTER TABLE webburza_sylius_location_translation ADD CONSTRAINT FK_3B2F83722C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES webburza_sylius_location (id) ON DELETE CASCADE',
            'ALTER TABLE webburza_sylius_location_type_translation ADD CONSTRAINT FK_FC2A05E12C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES webburza_sylius_location_type (id) ON DELETE CASCADE',
        ];

        $manager->beginTransaction();

        foreach ($queries as $query) {
            $statement = $manager->getConnection()->prepare($query);
            $statement->execute();
        }

        $manager->commit();
    }

    /**
     * Create all required permission entries.
     *
     * @param \Doctrine\ORM\EntityManager $manager
     */
    private function createPermissions($manager)
    {
        $repository = $this->getContainer()->get('sylius.repository.permission');

        // Get parent node (used for content)
        $contentPermission = $repository->findOneBy(['code' => 'sylius.content']);

        // Create permissions
        $locationManagePermission = $this->createLocationPermissions($contentPermission);
        $locationTypeManagePermission = $this->createLocationTypePermissions($contentPermission);

        // Persist the permissions
        $manager->persist($locationManagePermission);
        $manager->persist($locationTypeManagePermission);
        $manager->flush();
    }

    /**
     * Create permissions for Location resource.
     *
     * @param Permission $parentPermission
     *
     * @return Permission
     */
    private function createLocationPermissions($parentPermission)
    {
        // Create main permissions node
        $managePermission = new Permission();
        $managePermission->setCode('webburza_location.manage.location');
        $managePermission->setDescription('Manage locations');
        $managePermission->setParent($parentPermission);

        // Define permissions
        $permissions = [
            'webburza_location.location.show' => 'Show location',
            'webburza_location.location.index' => 'List locations',
            'webburza_location.location.create' => 'Create location',
            'webburza_location.location.update' => 'Update location',
            'webburza_location.location.delete' => 'Delete location',
            'webburza_location.location_image.delete' => 'Delete location image',
        ];

        // Create each permission
        foreach ($permissions as $code => $description) {
            $permission = new Permission();
            $permission->setCode($code);
            $permission->setDescription($description);

            $managePermission->addChild($permission);
        }

        return $managePermission;
    }

    /**
     * Create permissions for Location Type resource.
     *
     * @param Permission $parentPermission
     *
     * @return Permission
     */
    private function createLocationTypePermissions(Permission $parentPermission)
    {
        // Create main permissions node
        $managePermission = new Permission();
        $managePermission->setCode('webburza_location.manage.location_type');
        $managePermission->setDescription('Manage location types');
        $managePermission->setParent($parentPermission);

        // Define permissions
        $permissions = [
            'webburza_location.location_type.show' => 'Show location type',
            'webburza_location.location_type.index' => 'List location types',
            'webburza_location.location_type.create' => 'Create location type',
            'webburza_location.location_type.update' => 'Update location type',
            'webburza_location.location_type.delete' => 'Delete location type',
        ];

        // Create each permission
        foreach ($permissions as $code => $description) {
            $permission = new Permission();
            $permission->setCode($code);
            $permission->setDescription($description);

            $managePermission->addChild($permission);
        }

        return $managePermission;
    }
}