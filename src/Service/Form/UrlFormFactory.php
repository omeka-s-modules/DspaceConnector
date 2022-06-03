<?php
namespace DspaceConnector\Service\Form;

use DspaceConnector\Form\UrlForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UrlFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new UrlForm(null, $options ?? []);
        return $form;
    }
}
