<?php
return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/DscpaceConnector/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/DspaceConnector/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'dspace_items' => 'DspaceConnector\Api\Adapter\DspaceItemAdapter',
            'dspace_imports' => 'DspaceConnector\Api\Adapter\DspaceImportAdapter',
        ],
    ],
    'controllers' => [
        'factories' => [
            'DspaceConnector\Controller\Index' => 'DspaceConnector\Service\Controller\IndexControllerFactory',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/DspaceConnector/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'form_elements' => [
        'factories' => [
            'DspaceConnector\Form\ImportForm' => 'DspaceConnector\Service\Form\ImportFormFactory',
            'DspaceConnector\Form\UrlForm' => 'DspaceConnector\Service\Form\UrlFormFactory',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/DspaceConnector/src/Entity',
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Dspace Connector', // @translate
                'route' => 'admin/dspace-connector',
                'resource' => 'DspaceConnector\Controller\Index',
                'pages' => [
                    [
                        'label' => 'Import', // @translate
                        'route' => 'admin/dspace-connector',
                        'resource' => 'DspaceConnector\Controller\Index',
                    ],
                    [
                        'label' => 'Past Imports', // @translate
                        'route' => 'admin/dspace-connector/past-imports',
                        'controller' => 'Index',
                        'action' => 'past-imports',
                        'resource' => 'DspaceConnector\Controller\Index',
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'dspace-connector' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/dspace-connector',
                            'defaults' => [
                                '__NAMESPACE__' => 'DspaceConnector\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'past-imports' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/past-imports',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'DspaceConnector\Controller',
                                        'controller' => 'Index',
                                        'action' => 'past-imports',
                                    ],
                                ],
                            ],
                            'import' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/import',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'DspaceConnector\Controller',
                                        'controller' => 'Index',
                                        'action' => 'import',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
