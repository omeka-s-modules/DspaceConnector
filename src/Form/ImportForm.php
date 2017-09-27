<?php
namespace DspaceConnector\Form;

use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Form;

class ImportForm extends Form
{
    protected $owner;

    public function init()
    {
        $this->add([
            'name' => 'ingest_files',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Import files into Omeka S', // @translate
                'info' => 'If checked, original files will be imported into Omeka S. Otherwise, derivates will be displayed when possible, with links back to the original file in the DSpace repository.', // @translate
            ],
        ]);

        $this->add([
                'name' => 'itemSet',
                'type' => ResourceSelect::class,
                'options' => [
                    'label' => 'Item Set', // @translate
                    'info' => 'Optional. Import items into this item set.', // @translate
                    'empty_option' => 'Select item set', // @translate
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

        //slightly weird resetting of the values to add the create/update item set option to what
        //ResourceSelect builds for me
        $valueOptions = $itemSetSelect->getValueOptions();
        $valueOptions = ['new' => 'Create or update from DSpace collection'] + $valueOptions; // @translate
        $itemSetSelect->setValueOptions($valueOptions);

        //$this->add($itemSetSelect);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'itemSet',
            'required' => false,
        ]);

        $this->add([
            'name' => 'comment',
            'type' => 'textarea',
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'A note about the purpose or source of this import', // @translate
            ],
            'attributes' => [
                'id' => 'comment',
            ],
        ]);

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
