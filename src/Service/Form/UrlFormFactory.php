<?php
namespace DspaceConnector\Service\Form;

use DspaceConnector\Form\UrlForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UrlFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new UrlForm(null, $this->options);
        return $form;
    }
}
