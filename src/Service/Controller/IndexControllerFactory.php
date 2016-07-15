<?php
namespace DspaceConnector\Service\Controller;

use DspaceConnector\Controller\IndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {   
        $serviceLocator = $serviceLocator->getServiceLocator();
        $logger = $serviceLocator->get('Omeka\Logger');
        $jobDispatcher = $serviceLocator->get('Omeka\JobDispatcher');
        $client = $serviceLocator->get('Omeka\HttpClient');
        $indexController = new IndexController($logger, $jobDispatcher, $client);
        return $indexController;
    }
}
