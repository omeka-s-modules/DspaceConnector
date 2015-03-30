<?php
namespace DspaceConnector;

use Omeka\Module\AbstractModule;
use Omeka\Model\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("CREATE TABLE dspace_item (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, job_id INT NOT NULL, api_url VARCHAR(255) NOT NULL, remote_id INT NOT NULL, handle VARCHAR(255) NOT NULL, last_modified DATETIME NOT NULL, UNIQUE INDEX UNIQ_1C6D63B4126F525E (item_id), INDEX IDX_1C6D63B4BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4126F525E FOREIGN KEY (item_id) REFERENCES item (id);");
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);");
    }
    
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4126F525E;");
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4BE04EA9;");
        $connection->exec('DROP TABLE dspace_item');
    }
}

