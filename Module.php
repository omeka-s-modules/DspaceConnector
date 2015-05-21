<?php
namespace DspaceConnector;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\SharedEventManagerInterface;

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
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
        $connection->exec("ALTER TABLE dspace_item ADD CONSTRAINT FK_1C6D63B4BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);");
    }
    
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4126F525E;");
        $connection->exec("ALTER TABLE dspace_item DROP FOREIGN KEY FK_1C6D63B4BE04EA9;");
        $connection->exec('DROP TABLE dspace_item');
    }
    
    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager,
        SharedEventManagerInterface $filterManager
    ) {
        $sharedEventManager->attach(
                'Omeka\Controller\Admin\Item',
                'view.show.after',
                array($this, 'showSource')
                );
    }
    
    public function showSource($event) 
    {
        $view = $event->getTarget();
        $item = $view->item;
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('dspace_items', array('item_id' => $item->id()));
        if ($response->isError()) {

        }
        $dspaceItems = $response->getContent();
        if ($dspaceItems) {
            $dspaceItem = $dspaceItems[0];
            $url = $dspaceItem->apiUrl() . '/handle/' . $dspaceItem->handle();
            echo '<h3>' . $view->translate('Original')  . '</h3>';
            echo '<p>' . $view->translate('Last Modified') . ' ' . $view->i18n()->dateFormat($dspaceItem->lastModified()) . '</p>';
            echo '<p><a href="' . $url . '">' . $view->translate('Link') . '</a></p>';
        }
    }
    
}

