<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'DspaceConnector\Controller\Index' => 'DspaceConnector\Controller\IndexController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/module/DspaceConnector/view',
        ),
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/module/DspaceConnector/src/Model/Entity',
        ),
    ),
    'navigation' => array(
        'admin' => array(
            array(
                'label'      => 'Dspace Connector',
                'route'      => 'dspace-connector',
                'controller' => 'DspaceConnector',
                'action'     => 'index',
                'resource'   => 'DspaceConnector\Controller\Index',
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
