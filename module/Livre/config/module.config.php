<?php
return array(
     'controllers' => array(
         'invokables' => array(
             'Livre\Controller\Livre' => 'Livre\Controller\LivreController',
         ),
     ),
	 'router' => array(
         'routes' => array(
             'livre' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/livre[/:action][/:id]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'id'     => '[0-9]+',
                     ),
                     'defaults' => array(
                         'controller' => 'Livre\Controller\Livre',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),

     'view_manager' => array(
         'template_path_stack' => array(
             'livre' => __DIR__ . '/../view',
         ),
     ),
 );