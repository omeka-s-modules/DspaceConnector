<?php
namespace DspaceConnector\Service\Form;

use DspaceConnector\Form\ImportForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options ?? []);
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);
        $form->setUserSettings($services->get('Omeka\Settings\User'));
        $form->setApiManager($services->get('Omeka\ApiManager'));
        return $form;
    }
}
