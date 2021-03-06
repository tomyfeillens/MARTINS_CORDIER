<?php namespace Livre;

 use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
 use Zend\ModuleManager\Feature\ConfigProviderInterface;
 use Livre\Model\Livre;
 use Livre\Model\LivreTable;
 use Zend\Db\ResultSet\ResultSet;
 use Zend\Db\TableGateway\TableGateway;

class Module /*implements AutoloaderProviderInterface, ConfigProviderInterface*/
 {
     public function getAutoloaderConfig()
     {
         return array(
             'Zend\Loader\ClassMapAutoloader' => array(
                 __DIR__ . '/autoload_classmap.php',
             ),
             'Zend\Loader\StandardAutoloader' => array(
                 'namespaces' => array(
                     __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                 ),
             ),
         );
     }

     public function getConfig()
     {
         return include __DIR__ . '/config/module.config.php';
     }
	 
	 public function getServiceConfig()
     {
         return array(
             'factories' => array(
                 'Livre\Model\LivreTable' =>  function($sm) {
                     $tableGateway = $sm->get('LivreTableGateway');
                     $table = new LivreTable($tableGateway);
                     return $table;
                 },
                 'LivreTableGateway' => function ($sm) {
                     $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                     $resultSetPrototype = new ResultSet();
                     $resultSetPrototype->setArrayObjectPrototype(new Livre());
                     return new TableGateway('livre', $dbAdapter, null, $resultSetPrototype);
                 },
             ),
         );
     }
 }
