<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

$s = require_once('_outils.class.php');
if( $s != 1 ) 
	log::add('heatzy', 'error', __METHOD__.'(ln '.__LINE__.')'.' : error require_once _outils.class.php ='.$s);

$s = require_once('_bouchons.class.php');
if( $s != 1 ) 
	log::add('heatzy', 'error', __METHOD__.'(ln '.__LINE__.')'.' : error require_once _bouchons.class.php='.$s);

class hydrolinkhome extends eqLogic {
  /*     * *************************Attributs****************************** */
	public static $bouchon = true ;
  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function test(){
    
     //https://github.com/Roeli1996/ha-ecowater-hydrolink/blob/5ffbdd550d477a2a465dff33f7c0d0ef7126f40f/custom_components/ecowater_hydrolink_custom/const.py
    
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );
    
    	self::Login() ;
    	$result = self::getList() ;
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': test='.var_export( $result , true) );
  }
  
  public static function synchronise(){

    	$result = self::getList() ;
    	
    	if( $result != false ){
          log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $result[total]='.$result['total'] );
          if( $result['total'] == 0)
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Pas de devices trouves' );
            else{
              foreach ($result['data'] as $device) {
                log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$device['id'] );
                self::create( $device['id'] , $device ) ;
              }
            }
        }
  }
  
  public static function create( $deviceId , $data ){
            $eqLogic = eqLogic::byLogicalId( $deviceId , 'hydrolinkhome', false);
            if (! is_object($eqLogic)) {   /// Creation des devices inexistants
                $eqLogic = new hydrolinkhome();
                $eqLogic->setIsVisible(1);
                
                //$Nb_Add++ ;
                $return['new']++ ;
            }
            else{
              $return['update']++ ;
              log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Device '.$deviceId.' deja créé' );
            }
            
            $eqLogic->setEqType_name('hydrolinkhome');
    		$eqLogic->setName( $deviceId );
            $eqLogic->setLogicalId( $deviceId );
    		$eqLogic->setIsEnable(1);
    		$eqLogic->save();
  }
    
  
  //* Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function Login(){
   
/*"email": self.entry.data[CONF_USERNAME],
            "password": self.entry.data[CONF_PASSWORD]*/
    
    log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );
    
    if( self::$bouchon )
      return bouchons::getBouchon( 'login') ;
  
    	$User = config::byKey('email',__CLASS__,'');
    	$Passwd = config::byKey('password',__CLASS__,'');
    
        /// Preparation de la requete : json
        //$data = json_encode( array('email' => $User, 'password' => $Passwd, 'lang' => $Lang) ) ;
    	$data = json_encode( array('email' => $User, 'password' => $Passwd) ) ;
    
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $data='.$data );

        /// Parametres cUrl
        $params = array(
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    'Origin: https://app.hydrolinkhome.eu',
                    'Referer: https://app.hydrolinkhome.eu/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'X-Requested-With: XMLHttpRequest'
            ),
            CURLOPT_URL => 'https://api.hydrolinkhome.com/v1/auth/login',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $data
        );

        /// Initialisation de la ressources curl
        $gizwits = curl_init();
        if ($gizwits === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($gizwits, $params);
        
        /// Excute la requete
        $result = curl_exec($gizwits);

        /// Test le code retour http
        $httpcode = curl_getinfo($gizwits, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($gizwits);

        if( $httpcode != 200 && $httpcode != 400 ){
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode.' - '.$result );
            return false;
        }
        
        ///Décodage de la réponse
        $aRep = json_decode($result, true);
    
    	if( isset( $aRep['access_token'] ) ){
          config::save('access_token'   , $aRep['access_token' ]  , __CLASS__);
          config::save('refresh_token'  , $aRep['refresh_token']  , __CLASS__);
          config::save('user_id'        , $aRep['user_id']        , __CLASS__);
        }
    
        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return true;
  }
  
  //* Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function getList(){
    //https://api.hydrolinkhome.eu/v1/devices?all=false&per_page=200

        
    
    	$User = "" ;
    	$Passwd = '' ;
    	$Lang = 'en' ;
    	$token = '' ;
    
        /// Preparation de la requete : json
        //$data = json_encode( array('email' => $User, 'password' => $Passwd, 'lang' => $Lang) ) ;
    	$data = json_encode( array('email' => $User, 'Authorization' => $token) ) ;
    
        if( self::$bouchon )
      		return json_decode( bouchons::getBouchon( 'devices' ) , true) ;
    	else
          	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': PAS DE BOUCHON' );

        /// Parametres cUrl
        $params = array(
            //CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    'Origin: https://app.hydrolinkhome.com',
                    'Referer: https://app.hydrolinkhome.com/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
              		'Authorization: Bearer '.config::byKey('access_token',__CLASS__,'')
            ),
            CURLOPT_URL => 'https://api.hydrolinkhome.com/v1/devices?all=false&per_page=200',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10
            //CURLOPT_POSTFIELDS => $data
        );

        /// Initialisation de la ressources curl
        $gizwits = curl_init();
        if ($gizwits === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($gizwits, $params);
        
        /// Excute la requete
        $result = curl_exec($gizwits);

        /// Test le code retour http
        $httpcode = curl_getinfo($gizwits, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($gizwits);
    
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $params='.var_export( $params , true) );

        if( $httpcode != 200 && $httpcode != 400 ){
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode.' - '.$result );
            return false;
        }
        
        ///Décodage de la réponse
        //$aRep = json_decode($result, true);
    
        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return json_decode($result, true);
  
  }
          
//* Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function getDetail(){
    //https://api.hydrolinkhome.eu/v1/devices/{self.device_id}/live"

        
    
    	$User = "" ;
    	$Passwd = '' ;
    	$Lang = 'en' ;
    	$token = '' ;
    
        /// Preparation de la requete : json
        //$data = json_encode( array('email' => $User, 'password' => $Passwd, 'lang' => $Lang) ) ;
    	$data = json_encode( array('email' => $User, 'Authorization' => $token) ) ;

        /// Parametres cUrl
        $params = array(
            //CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    'Origin: https://app.hydrolinkhome.eu',
                    'Referer: https://app.hydrolinkhome.eu/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'X-Requested-With: XMLHttpRequest',
              		'Authorization: Bearer '.$token
            ),
            CURLOPT_URL => 'https://api.hydrolinkhome.eu/v1/devices?all=false&per_page=200',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $data
        );

        /// Initialisation de la ressources curl
        $gizwits = curl_init();
        if ($gizwits === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($gizwits, $params);
        
        /// Excute la requete
        $result = curl_exec($gizwits);

        /// Test le code retour http
        $httpcode = curl_getinfo($gizwits, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($gizwits);
    
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $params='.$params );

        if( $httpcode != 200 && $httpcode != 400 ){
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode );
            return false;
        }
        
        ///Décodage de la réponse
        //$aRep = json_decode($result, true);
    
        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return true;
  
  }
  
  
  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
        /// Creation de la commande de rafraichissement
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new heatzyCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
            $refresh->setLogicalId('refresh');
            $refresh->setType('action');
            $refresh->setSubType('other');
            $refresh->setEqLogic_id($this->getId());
            $refresh->setIsHistorized(0);
            $refresh->setIsVisible(1);
            $refresh->save();
        }
    
	        /// Creation de la commande info etatprog binaire
	        $etat = $this->getCmd(null, 'etatprog');
	        if (!is_object($etat)) {
	            $etat = new heatzyCmd();
	            $etat->setName(__('Etat programmation', __FILE__));
	            $etat->setLogicalId('etatprog');
	            $etat->setType('info');
	            $etat->setSubType('binary');
	            $etat->setEqLogic_id($this->getId());
	            $etat->setIsHistorized(0);
	            $etat->setIsVisible(1);
	            $etat->save();
	        }
    
        /// Creation de la commande info Etat numeric
        $etat = $this->getCmd(null, 'Etat');
        if (!is_object($etat)) {
            $etat = new heatzyCmd();
            $etat->setName(__('Etat', __FILE__));
            $etat->setLogicalId('etat');
            $etat->setType('info');
            $etat->setSubType('numeric');
            $etat->setEqLogic_id($this->getId());
            $etat->setIsHistorized(0);
            $etat->setIsVisible(1);
            $etat->save();
        }
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $mode = $this->getCmd(null, 'mode');
        if (!is_object($mode)) {
            $mode = new heatzyCmd();
            $mode->setName(__('Mode', __FILE__));
            $mode->setLogicalId('mode');
            $mode->setType('info');
            $mode->setSubType('string');
            $mode->setEqLogic_id($this->getId());
            $mode->setIsHistorized(0);
            $mode->setIsVisible(1);
            $mode->save();
        }
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class hydrolinkhomeCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}