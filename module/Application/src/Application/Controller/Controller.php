<?php

namespace Application\Controller;
    use Zend\Mvc\Controller\AbstractActionController;
    use Zend\View\Model\ViewModel;
    use Zend\Authentication\Result;
    use Zend\Authentication\Storage\Session;
    use Application\Auth\Adapter;
    use Application\Auth\AuthenticationService;
    use Application\Auth\SaltCellar;
    class AuthController extends AbstractActionController
    {
        protected $saltData = array(
                                  'years' =>  array(2011 => 'teg', 2012 => 'e', 2013 => 'qze'),  
                                  'months' => array(1 => 'fsq', 2 => '02', 3 => 'q', 4 => 'gdsg', 5 => '(3339', 
                                                    6 => 'fdERs', 7 => 'FDS45$q', 8 => '[sxdi', 9 => 'Fdsdf', 10 => 'FDSDfeh', 11 => 'FDFDSFSDFSs',
                                                    12 => 'FDSFDSF5423Q'
                                                   ),
                                  'days' =>   array(1 => 'vcxv', 2 => 'gfvc"', 3 => 'RG', 4 => 'FDSFvcxz', 5 => 'gfdgfg', 6 => 'vd', 7 => 'ret', 8 => 'dfs:', 9 => './DF./DSF',
                                                    10 => 'flgdoh', 11 => 'cvxcryk', 12 => 'VVVv', 13 => 'tokykoh', 14 => 'fglfljutj', 15 => 'yujkh,', 16 => 'dnnbi', 17 => 'fdvb;mfdg', 18 => '_JS2---2UIne', 19 => 'fhopsdf', 20 => '43IT54IG9,nvb', 21 => '34594TK?GD',
                                                    22 => 'jlhjlteyrt', 23 => 'Fdvbb,n', 24 => 'bvcgjytutuy', 25 => 'Fdbvcbv,nb,', 26 => 'ythgbdfgfdg', 27 => 'vcgfyty', 28 => 'Gdfsgfdg48', 29 => 'fdsgtree', 30 => 'fgpdtdpoytre99', 31 => 'FDSP04Rofgfdng'
                                                   )    
                              );
        protected $fingerHash = array('start' => 'st$^ar', 'end' => 'tend');
        protected $model = array('login' => 'login_admin', 'password' => 'password_admin',
                                 'mail' => 'vail_admin', 'role' => 'role_admin', 'access' => 'access_admin'
                                );
        protected $serializedCols = array('access');
        protected $defaultColumns = array('login' => 'login_admin', 'password' => 'password_admin');
        protected $modelColumns = array('login' => 'login', 'password' => 'password');
        
        /**
         * Log user.
         */
        public function loginAction()
        {
            // Set up the authentication adapter
            $authAdapter = new Adapter($this->getServiceLocator()->get("zenddbadapteradapter"), "admin", $this->defaultColumns['login'], $this->defaultColumns['password'], "SHA1(?)", "created_admin", true);
            $authAdapter->setSerializedCols($this->serializedCols);
            $authAdapter->setIdentity('bartosz');
            $authAdapter->setCredential('bartosz');
            $authAdapter->setSaltCellar(new SaltCellar($this->saltData));
            $authAdapter->setModelArray($this->model);
            $authAdapter->setFingerHash($this->fingerHash);
            $authAdapter->setSessionTimeout(1800); // 30 minutes
            
            // instantiate the authentication service
            $auth = new AuthenticationService();
            $auth->setStorage(new Session('front'));
            $auth->setModelArrayColumns($this->modelColumns);
            $result = $auth->authenticate($authAdapter);
            switch($result->getCode())
            {
                // identity doesn't exist
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    echo "Identity not found";
                break;
                // invalid credentials
                case Result::FAILURE_CREDENTIAL_INVALID:
                    echo "Credentials invalid";
                break;
                // OK
                case Result::SUCCESS:
                    echo "OK";
                break;
                // default action;
                default:
                    echo "Default";
                break;
            }
            if($result->isValid())
            {
                echo "Logged correctly";
            }
            else
            {
                echo "Logged incorrectly";
            }
            die();
        }
        
        /**
         * Checks user by storage data.
         */
        public function checkAction()
        {
            $authAdapter = new Adapter($this->getServiceLocator()->get("zenddbadapteradapter"), "admin", $this->defaultColumns['login'], $this->defaultColumns['password'], "SHA1(?)", "created_admin", true);
            $authAdapter->setSerializedCols($this->serializedCols);
            $authAdapter->setSaltCellar(new SaltCellar($this->saltData));
            $authAdapter->setModelArray($this->model);
            $authAdapter->setFingerHash($this->fingerHash);
            $authAdapter->setSessionTimeout(1800);
            $auth = new AuthenticationService();
            $auth->setRegenerateId(true);
            $auth->setStorage(new Session('front'));
            $auth->setModelArrayColumns($this->modelColumns);
            $result = $auth->authenticate($authAdapter);
            $identity = $auth->getIdentity();    
            if($auth->hasIdentity())
            {
                echo "has identity";
            }
            else
            {
                echo "hasn't identity";
            }
            die();
        }
        
    }