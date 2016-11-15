<?php
namespace DspaceConnector\Form;

use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Form;
use Zend\Validator\Callback;

class ImportForm extends Form
{

    protected $owner;

    public function init()
    {
        $this->add(array(
            'name' => 'ingest_files',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Import files into Omeka', // @translate
                'info'  => 'If checked, original files will be imported into Omeka. Otherwise, derivates will be displayed when possible, with links back to the original file in the repository.' // @translate
            )
        ));

        //$serviceLocator = $this->getServiceLocator();
        //$auth = $serviceLocator->get('Omeka\AuthenticationService');

        //$itemSetSelect = new ResourceSelect($serviceLocator);

        $this->add([
                'name'    => 'itemSet',
                'type'    => ResourceSelect::class,
                'options' => [
                    'label' => 'Item Set', // @translate
                    'info' => 'Optional. Import items into this item set.', // @translate
                    'empty_option' => 'Select Item Set', // @translate
                    'resource_value_options' => [
                        'resource' => 'item_sets',
                        'query' => [],
                        'option_text_callback' => function ($itemSet) {
                            return $itemSet->displayTitle();
                        },
                    ],
                ],
        ]);
        $itemSetSelect = $this->get('itemSet');
        /*
        $itemSetSelect->setName('itemSet')
            ->setLabel('Import into')
            ->setOption('info', 'Optional. Import items into this item set.') // @translate
            ->setEmptyOption('Select Item Set...') // @translate
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $this->getOwner()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]'); // @translate
                }
            );
            */
        //slightly weird resetting of the values to add the create/update item set option to what
        //ResourceSelect builds for me
        $valueOptions = $itemSetSelect->getValueOptions();
        $valueOptions = array('new' => 'Create or update from DSpace Collection') + $valueOptions; // @translate
        $itemSetSelect->setValueOptions($valueOptions);

        //$this->add($itemSetSelect);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'itemSet',
            'required' => false,
        ));

        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Comment', // @translate
                'info'  => 'A note about the purpose or source of this import.' // @translate
            ),
            'attributes' => array(
                'id' => 'comment'
            )
        ));
    }

    public function setOwner($identity)
    {
        $this->owner = $identity;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
