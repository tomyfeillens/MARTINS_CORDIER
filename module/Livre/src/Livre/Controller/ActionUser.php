<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Action
 *
 * @author p1406056
 */
class  ActionUser extends AbstractActionController { 
    
    public static function getUser(){
        return $this->getAuthService()->getStorage()->read();
    }
}
