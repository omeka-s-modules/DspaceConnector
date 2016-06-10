<?php
namespace DspaceConnector\Service\Form;

use DspaceConnector\Form\ImportForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ImportFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new ImportForm(null, $this->options);
        $serviceLocator = $elements->getServiceLocator();
        $identity = $serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);
        return $form;
    }
}
