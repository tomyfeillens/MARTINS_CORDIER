<?php

    namespace Application\Auth;
    use Zend\Authentication\AuthenticationService as BaseAuthenticationService;
    use Zend\Authentication\Adapter\AdapterInterface;
    use Zend\Authentication\Result;
    class AuthenticationService extends BaseAuthenticationService
    {
        /**
         * Determines if we have to regenerate session id.
         * @access protected
         * @var boolean
         */
        protected $regenerateId = false;
        /**
         * Array with columns containing login and password in personalized identity response of Adapter.
         * If not empty, must contain "login" and "password" keys.
         * @access protected
         * @var array
         */
        protected $modelArrayColumns = array();
        /**
         * Array with columns containing login and password in default identity response of Adapter.
         * If not empty, must contain "login" and "password" keys.
         * @access protected
         * @var array
         */
        protected $defaultColumns = array();
        
        // @Override
        public function authenticate(AdapterInterface $adapter = null)
        {
            if (!$adapter) {
                if (!$adapter = $this->getAdapter()) {
                    throw new \Exception('An adapter must be set or passed prior to calling authenticate()');
                }
            }
            if($this->hasIdentity())
            {
                $identity = $this->getIdentity();
                // if some of fields is empty, put '.' - otherwise DbTable will return a RuntimeException
                if(!isset($identity[$this->getIdentityColumn('login')])) $identity[$this->getIdentityColumn('login')] = '.';
                if(!isset($identity[$this->getIdentityColumn('password')])) $identity[$this->getIdentityColumn('password')] = '.';
                if(!isset($identity['signature'])) $identity['signature'] = '.';
                if(!isset($identity['timeout'])) $identity['timeout'] = '.';
                $adapter->setFirstLogin(false);
                $adapter->setIdentity($identity[$this->getIdentityColumn('login')]);
                $adapter->setCredential($identity[$this->getIdentityColumn('password')]);
                $adapter->setSessionFingerprinting($identity['signature']);
                $adapter->setSessionLimit($identity['timeout']);
            }
            $result = parent::authenticate($adapter);
            if(Result::SUCCESS == $result->getCode() && ($this->regenerateId || time()%2 == 0))
            {
                session_regenerate_id(true);
            }
            return $result;
        }
        /**
         * Setters.
         */
        public function setRegenerateId($r)
        {
            $this->regenerateId = $r;
        }
        public function setModelArrayColumns($col)
        {
            $this->modelArrayColumns = $col;
        }
        public function setDefaultColumns($col)
        {
            $this->defaultColumns = $col;
        }
        
        /**
         * Gets identity column. If $modelArray was set in Adapter, the columns must correspond to it.
         * @access protected
         * @param String $field Field name.
         * @return String Default field name or $modelArray's field name.
         */
        protected function getIdentityColumn($field)
        {
            if(array_key_exists($field, $this->modelArrayColumns)) return $this->modelArrayColumns[$field];
            return $this->defaultColumns[$field];
        }
    }

