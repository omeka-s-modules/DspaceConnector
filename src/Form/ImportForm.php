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
        
        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        
        $itemSetSelect = new ResourceSelect($serviceLocator);
        
        
        $itemSetSelect->setName('itemSet')
            ->setLabel('Import into')
            ->setOption('info', $translator->translate('Optional. Import items into this item set.'))
            ->setEmptyOption('Select Item Set...')
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $auth->getIdentity()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]');
                }
            );
        //slightly weird resetting of the values to add the create/update item set option to what
        //ResourceSelect builds for me
        $valueOptions = $itemSetSelect->getValueOptions();
        $valueOptions = array('new' => $translator->translate('Create or update from DSpace Collection')) + $valueOptions;
        $itemSetSelect->setValueOptions($valueOptions);

        $this->add($itemSetSelect);
        
        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'itemSet',
            'required' => false,
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