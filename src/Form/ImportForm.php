<?php
namespace DspaceConnector\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;

class ImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();
        
        $this->add(array(
            'name' => 'api_url',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('DSpace site URL'),
                'info'  => $translator->translate('The URL of the repository you want to connect to. Fill this in, then click "Get Collections" or "Get Communities" below to browse what you want to import.')
            ),
            'attributes' => array(
                'id' => 'api-url'
            )
        ));
        
        $this->add(array(
            'name' => 'ingest_files',
            'type' => 'checkbox',
            'options' => array(
                'label' => $translator->translate('Import files into Omeka'),
                'info'  => $translator->translate('If checked, original files will be imported into Omeka. Otherwise, derivates will be displayed when possible, with links back to the original file in the repository.')
            )
        ));
        
        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
                'info'  => $translator->translate('A note about the purpose or source of this import.')
            ),
            'attributes' => array(
                'id' => 'comment'
            )
        ));
    }
    
}