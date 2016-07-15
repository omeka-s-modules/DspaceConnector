<?php
return array(
    'api_adapters' => array(
        'invokables' => array(
            'dspace_items'   => 'DspaceConnector\Api\Adapter\DspaceItemAdapter',
            'dspace_imports' => 'DspaceConnector\Api\Adapter\DspaceImportAdapter'
        ),
    ),
    'controllers' => array(
        'factories' => array(
            'DspaceConnector\Controller\Index' => 'DspaceConnector\Service\Controller\IndexControllerFactory',
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/DspaceConnector/view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    'form_elements' => [
        'factories' => [
            'DspaceConnector\Form\ImportForm' => 'DspaceConnector\Service\Form\ImportFormFactory',
            'DspaceConnector\Form\UrlForm' => 'DspaceConnector\Service\Form\UrlFormFactory',
        ],
    ],
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/DspaceConnector/src/Entity',
        ),
    ),
    'navigation' => array(
        'AdminModule' => array(
            array(
                'label'      => 'Dspace Connector',
                'route'      => 'admin/dspace-connector',
                'resource'   => 'DspaceConnector\Controller\Index',
                'pages'      => array(
                    array(
                        'label'      => 'Import',
                        'route'      => 'admin/dspace-connector',
                        'resource'   => 'DspaceConnector\Controller\Index',
                    ),
                    array(
                        'label'      => 'Past Imports',
                        'route'      => 'admin/dspace-connector/past-imports',
                        'controller' => 'Index',
                        'action'     => 'past-imports',
                        'resource'   => 'DspaceConnector\Controller\Index',
                    ),
                ),
            ),
        ),
    ),
    'router' => array(
        'routes' => array(
            'admin' => array(
                'child_routes' => array(
                    'dspace-connector' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/dspace-connector',
                            'defaults' => array(
                                '__NAMESPACE__' => 'DspaceConnector\Controller',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'past-imports' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/past-imports',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'DspaceConnector\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'past-imports',
                                    ),
                                )
                            ),
                            'import' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/import',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'DspaceConnector\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'import',
                                    ),
                                )
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
