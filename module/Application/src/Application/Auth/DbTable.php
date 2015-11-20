<?php

    namespace Application\Auth;
    use Zend\Authentication\Adapter\AdapterInterface;
    use Zend\Authentication\Result;
    use Zend\Authentication\Adapter\DbTable;
    use Zend\Db\Adapter\Adapter as DbAdapter;
    use Zend\Db\Sql\Select;
    use Zend\Db\ResultSet\ResultSet;
    use Application\Auth\SaltCellar;
    class Adapter extends DbTable
    {
        /**
         * Instance of salt cellar.
         * @access protected
         * @var Application\Auth\SaltCellar
         */
        protected $saltCellar;
        /**
         * Column with user register date.
         * @access protected
         * @var String
         */
        protected $dateColumn;
        /**
         * Checks if current authentication is the first attempt of the user.
         * @access protected
         * @var boolean
         */
        protected $firstLogin;
        /**
         * Array with relations between database columns and desired authentication columns in returner array.
         * For exemple : array("database_login" => "login", "database_access" => "access")
         * @access protected
         * @var array
         */
        protected $modelArray;
        /**
         * Values added to generated fingerprinting hash. Must contain "start" and "end" keys.
         * @access protected
         * @var array
         */
        protected $fingerHash = array('start' => '', 'end' => '');
        /**
         * User's fingerprinting proof. It's generated on every Adapter's call.
         * @access protected
         * @var String
         */
        protected $fingerprinting = '';
        /**
         * Fingerprinting taken from authentication storage (for exemple : session storage).
         * @access protected
         * @var String
         */
        protected $sessionFingerprinting = '.';
        /**
         * Session's expiration time (in seconds).
         * @access protected
         * @var int
         */
        protected $sessionTimeout = 0;
        /** 
         * Maximal time (in seconds) of session persistance.
         * @access protected
         * @var int
         */
        protected $sessionLimit;
        /**
         * Columns which are serialized in the database.
         * @access protected
         * @var array
         */
        protected $serializedCols = array();
         
        public function __construct(DbAdapter $zendDb, $tableName = null, $identityColumn = null,
                                    $credentialColumn = null, $credentialTreatment = null, $dateColumn = null, $firstLogin = true)
        {
            parent::__construct($zendDb, $tableName, $identityColumn, $credentialColumn, $credentialTreatement);
            if($dateColumn !== null) $this->setDateColumn($dateColumn);
            if(isset($firstLogin))   $this->setFirstLogin($firstLogin);
        }
        // @Override
        protected function _authenticateCreateSelect()
        {
            $dbSelect = clone $this->getDbSelect();
            $dbSelect->from($this->tableName);
            $dbSelect->columns(array('*'));   
            $dbSelect->where($this->zendDb->getPlatform()->quoteIdentifier($this->identityColumn).' = ?');
            return $dbSelect;
        }
        
        // @Override
        protected function _authenticateValidateResult($resultIdentity)
        {
            if(!($comparedSession = $this->compareSession()) || !$this->checkSaltPassword($resultIdentity) || 
               $resultIdentity[$this->identityColumn] != $this->identity
            )
            {
                $this->authenticateResultInfo['code'] = Result::FAILURE_CREDENTIAL_INVALID;
                $this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
                return $this->_authenticateCreateAuthResult();
            }
            $this->resultRow = $resultIdentity;
            $this->makeFingerprinting();
            $this->authenticateResultInfo['identity'] = $this->createIdentityArray($resultIdentity);
            $this->authenticateResultInfo['code'] = Result::SUCCESS;
            $this->authenticateResultInfo['messages'][] = 'Authentication successful.';
            return $this->_authenticateCreateAuthResult();
        }
         
        // @Override 
        protected function _authenticateQuerySelect(Select $dbSelect)
        {
            $statement = $this->zendDb->createStatement();
            $dbSelect->prepareStatement($this->zendDb, $statement);
            $resultSet = new ResultSet();
            try {
                $resultSet->initialize($statement->execute(array($this->identity)));
                $resultIdentities = $resultSet->toArray();
            } catch (\Exception $e) {
                throw new Exception\RuntimeException(
                    'The supplied parameters to DbTable failed to '
                        . 'produce a valid sql statement, please check table and column names '
                        . 'for validity.', 0, $e
                );
            }
            return $resultIdentities;
        } 
        /**
         * Generates user's fingerpriting proof.
         * @access private
         * @return void
         */
        private function makeFingerprinting()
        {
            $this->fingerprinting = sha1($this->fingerHash['start'].$_SERVER['HTTP_USER_AGENT']."".$_SERVER['SERVER_ADDR']."".$_SERVER['SERVER_PROTOCOL']."rtrhvcs".$_SERVER['HTTP_ACCEPT_ENCODING'].$this->fingerHash['end']);    
        }
        /**
         * Checks salled password.
         * @access protected
         * @param array $resultIdentity Result of authentication process.
         * @return boolea True if password is correct, false otherwise.
         */
        protected function checkSaltPassword($resultIdentity) 
        {
            if($this->firstLogin)
            {
                if(!isset($this->saltCellar) || !($this->saltCellar instanceof SaltCellar)) return false;
                $createdDate = strtotime($resultIdentity[$this->dateColumn]);
                $salt = $this->saltCellar->getSalt(date('Y-m-d', $createdDate));
                $passSalt = sha1($this->saltCellar->setHash(array('salt' => $salt, 'mdp' => $this->credential, 'login' => $this->identity), date('n', $createdDate))); 
                return $passSalt == $resultIdentity[$this->credentialColumn];
            }
            else
            {
                return $resultIdentity[$this->credentialColumn] == $this->credential;
            }
        }
        /**
         * Checks if session's values are correct.
         * @access protected
         * @return boolean True if they are correct, false otherwise.
         */
        protected function compareSession()
        {
            if($this->firstLogin) return true;
            if($this->fingerprinting == "") $this->makeFingerprinting();
            if(!$this->firstLogin && ($this->fingerprinting != $this->sessionFingerprinting) || $this->sessionIsTimeout())
            {
              return false;
            }
            return true;
        }
        
        /**
         * Checks if session is expired.
         * @access public
         * @return boolean True if session is expired, false otherwise.
         */
        public function sessionIsTimeout()
        {
            return time() > $this->sessionLimit;
        }
        /**
         * Creates an identity array based on $this->modelArray values.
         * @access protected
         * @param array $identity Result of authentication process.
         * @return array Identity array
         */
        protected function createIdentityArray($identity)
        {
            // if no session timeout specified, set 30 minutes by default
            if($this->sessionTimeout == 0) $this->setSessionTimeout(1800);
            if(count($this->modelArray) > 0)
            {
                foreach($this->modelArray as $key => $value)
                {
                    if(in_array($key, $this->serializedCols))
                    {
                        $identity[$key] = unserialize($identity[$value]);            
                    }
                    else
                    {
                        $identity[$key] = $identity[$value];
                    }
                }
            }
            $identity['signature'] = $this->fingerprinting;
            $identity['timeout'] = time()+$this->sessionTimeout;
            return $identity;
        }
        /**
         * Setters
         */
        public function setSerializedCols($cols)
        {
            $this->serializedCols = $cols;
        }
        
        public function setSaltCellar(SaltCellar $cellar)
        {
            $this->saltCellar = $cellar;
        }
        public function setDateColumn($d)    
        {    
            $this->dateColumn = $d;    
        }
        
        public function setFirstLogin($f)
        {
            $this->firstLogin = $f;
        }
            
        public function setFingerHash($hash)
        {
            if(!array_key_exists('start', $hash))
            {
                throw new \Exception('Fingerhash array must contain "start" key');
            }
            if(!array_key_exists('end', $hash))
            {
                throw new \Exception('Fingerhash array must contain "end" key');
            }
            $this->fingerHash = $hash;
        } 
            
        public function setSessionFingerprinting($s)
        {
            $this->sessionFingerprinting = $s;
        }
        public function setSessionTimeout($t)
        {
            $this->sessionTimeout = $t;
        }
        public function setSessionLimit($l)
        {
            $this->sessionLimit = $l;
        }    
        
        public function setModelArray($model)    
        {
            $this->modelArray = $model;
        }
        /**
         * Getters.
         */
        public function getSerializedCols()
        {
            return $this->serializedCols;
        }    
        
        public function getSaltCellar()    
        {
            return $this->saltCellar;
        }
    }