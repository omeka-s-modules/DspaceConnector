<?php
namespace DspaceConnector\Form;

use Omeka\Form\Element\ResourceSelect;
use Laminas\Form\Form;

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
            'name' => 'itemSets',
            'type' => ResourceSelect::class,
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select item set(s)', // @translate
                'multiple' => true,
                'id' => 'item-set',
            ],
            'options' => [
                'label' => 'Item Sets', // @translate
                'info' => 'Optional. Import items into item set(s).', // @translate
                'empty_option' => '',
                'resource_value_options' => [
                    'resource' => 'item_sets',
                    'query' => [],
                    'option_text_callback' => function ($itemSet) {
                        return $itemSet->displayTitle();
                    },
                ],
            ],
        ]);
        $itemSetSelect = $this->get('itemSets');

        //slightly weird resetting of the values to add the create/update item set option to what
        //ResourceSelect builds for me
        $valueOptions = $itemSetSelect->getValueOptions();

        $valueOptions = ['new' => 'Create or update from DSpace collection'] + $valueOptions; // @translate
        $itemSetSelect->setValueOptions($valueOptions);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'itemSets',
            'required' => false,
        ]);

        $this->add([
            'name' => 'ignored_fields',
            'type' => 'text',
            'options' => [
                'label' => 'Ignored fields', // @translate
                'info' => 'DSpace fields to ignore, separated by commas', // @translate
            ],
            'attributes' => [
                'id' => 'ignored-fields',
            ],
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
