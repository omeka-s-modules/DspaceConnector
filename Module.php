<?php
namespace DspaceConnector;

use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Composer\Semver\Comparator;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            ['DspaceConnector\Api\Adapter\DspaceItemAdapter'],
            ['search', 'read']
            );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("CREATE TABLE dspace_item (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, job_id INT NOT NULL, api_url VARCHAR(255) NOT NULL, remote_id VARCHAR(36) NOT NULL, handle VARCHAR(255) NOT NULL, last_modified DATETIME NOT NULL, UNIQUE INDEX UNIQ_1C6D63B4126F525E (item_id), INDEX IDX_1C6D63B4BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);");

        $connection->exec("CREATE TABLE dspace_import (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, undo_job_id INT DEFAULT NULL, added_count INT NOT NULL, updated_count INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_56197DADBE04EA9 (job_id), UNIQUE INDEX UNIQ_56197DAD4C276F75 (undo_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");
        $connection->exec("ALTER TABLE dspace_import ADD CONSTRAINT FK_56197DADBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);");
        $connection->exec("ALTER TABLE dspace_import ADD CONSTRAINT FK_56197DAD4C276F75 FOREIGN KEY (undo_job_id) REFERENCES job (id);");
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4126F525E;");
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4BE04EA9;");
        $connection->exec('DROP TABLE dspace_item');

        $connection->exec("ALTER TABLE dspace_import DROP FOREIGN KEY FK_56197DADBE04EA9");
        $connection->exec("ALTER TABLE dspace_import DROP FOREIGN KEY FK_56197DAD4C276F75");
        $connection->exec('DROP TABLE dspace_import');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        if (Comparator::lessThan($oldVersion, '0.4.0-alpha')) {
            $connection = $serviceLocator->get('Omeka\Connection');
            $connection->exec("ALTER TABLE `dspace_item` CHANGE `remote_id` `remote_id` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
                'Omeka\Controller\Admin\Item',
                'view.show.after',
                [$this, 'showSource']
                );
    }

    public function showSource($event)
    {
        $view = $event->getTarget();
        $item = $view->item;
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('dspace_items', ['item_id' => $item->id()]);
        $dspaceItems = $response->getContent();
        if ($dspaceItems) {
            $dspaceItem = $dspaceItems[0];
            $url = 'http://hdl.handle.net/' . $dspaceItem->handle();
            echo '<h3>' . $view->translate('Original') . '</h3>';
            echo '<p>' . $view->translate('Last Modified') . ' ' . $view->i18n()->dateFormat($dspaceItem->lastModified()) . '</p>';
            echo '<p><a href="' . $url . '">' . $view->translate('Link') . '</a></p>';
        }
    }
}
