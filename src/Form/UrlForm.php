<?php
namespace DspaceConnector\Form;

use Zend\Form\Form;
use Zend\Form\Element\Url;
use Zend\Form\Element\Text;

class UrlForm extends Form
{
    public function init()
    {
        $this->setAttribute('action', 'dspace-connector/import');
        $this->add(array(
            'name' => 'api_url',
            'type' => Url::class,
            'options' => array(
                'label' => 'DSpace site URL', // @translate
                'info'  => 'The URL of the repository you want to connect to. (DSpace 5.6 or higher) Fill this in, then click "Get Collections" or "Get Communities" below to browse what you want to import.' // @translate
            ),
            'attributes' => array(
                'id' => 'api-url',
                'required' => 'true'
            )
        ));
        
        $this->add([
            'name' => 'endpoint',
            'type' => Text::class,
            'options' => [
                'label' => 'Endpoint', // @translate
                'info'  => 'The endpoint for the API', // @translate
            ],
            'attributes' => [
                'id'       => 'endpoint',
                'required' => 'false',
                'value'    => 'rest'
            ],
        ]);
    }
}
