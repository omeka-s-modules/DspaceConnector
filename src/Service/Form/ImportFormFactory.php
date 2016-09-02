<?php
namespace DspaceConnector\Service\Form;

use DspaceConnector\Form\ImportForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options);
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);
        return $form;
    }
}
