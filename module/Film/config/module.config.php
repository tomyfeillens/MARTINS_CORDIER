<?php
return array(
     'controllers' => array(
         'invokables' => array(
             'Film\Controller\Film' => 'Film\Controller\FilmController',
         ),
     ),
	 'router' => array(
         'routes' => array(
             'film' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/film[/:action][/:id]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'id'     => '[0-9]+',
                     ),
                     'defaults' => array(
                         'controller' => 'Film\Controller\Film',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),

     'view_manager' => array(
         'template_path_stack' => array(
             'film' => __DIR__ . '/../view',
         ),
     ),
 );