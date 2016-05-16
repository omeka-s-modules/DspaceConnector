<?php
namespace DspaceConnector\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;

class UrlForm extends AbstractForm
{
    public function buildForm()
    {
        $this->setAttribute('action', 'dspace-connector/import');
        $this->add(array(
            'name' => 'api_url',
            'type' => 'url',
            'options' => array(
                'label' => 'DSpace site URL', // @translate
                'info'  => 'The URL of the repository you want to connect to. (DSpace 4.x or higher) Fill this in, then click "Get Collections" or "Get Communities" below to browse what you want to import.' // @translate
            ),
            'attributes' => array(
                'id' => 'api-url',
                'required' => 'true'
            )
        ));
    }
}
