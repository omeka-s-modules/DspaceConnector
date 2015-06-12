<?php
return array(
    'api_adapters' => array(
        'invokables' => array(
            'dspace_items'   => 'DspaceConnector\Api\Adapter\DspaceItemAdapter',
            'dspace_imports' => 'DspaceConnector\Api\Adapter\DspaceImportAdapter'
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'DspaceConnector\Controller\Index' => 'DspaceConnector\Controller\IndexController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/DspaceConnector/view',
        ),
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/DspaceConnector/src/Entity',
        ),
    ),
    'navigation' => array(
        'admin' => array(
            array(
                'label'      => 'Dspace Connector',
                'route'      => 'dspace-connector',
                'resource'   => 'DspaceConnector\Controller\Index',
                'pages'      => array(
                    array(
                        'label'      => 'Import',
                        'route'      => 'dspace-connector/default',
                        'resource'   => 'DspaceConnector\Controller\Index',
                    ),
                    array(
                        'label'      => 'Past Imports',
                        'route'      => 'dspace-connector/default',
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
            'dspace-connector' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin/dspace-connector',
                    'defaults' => array(
                        '__NAMESPACE__' => 'DspaceConnector\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'id' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller/:id[/[:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '\d+',
                            ),
                            'defaults' => array(
                                'action' => 'show',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
