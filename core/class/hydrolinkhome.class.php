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
	log::add('hydrolinkhome', 'error', __METHOD__.'(ln '.__LINE__.')'.' : error require_once _outils.class.php ='.$s);

$s = require_once('_bouchons.class.php');
if( $s != 1 ) 
	log::add('hydrolinkhome', 'error', __METHOD__.'(ln '.__LINE__.')'.' : error require_once _bouchons.class.php='.$s);

$s = require_once('_api_hydrolinkhome.class.php');
if( $s != 1 ) 
	log::add('hydrolinkhome', 'error', __METHOD__.'(ln '.__LINE__.')'.' : error require_once _api_hydrolinkhome.class.php='.$s);

class hydrolinkhome extends eqLogic {
  /*     * *************************Attributs****************************** */
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
    
    	api_hydrolinkhome::Login() ;
    	$result = api_hydrolinkhome::getList() ;
    	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': test='.var_export( $result , true) );
  }
  
  public static function synchronise(){
		log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );
    
    	$result = api_hydrolinkhome::getList() ;
    	
    	if( $result != false ){
          log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $result[total]='.$result['total'] );
          if( $result['total'] == 0)
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Pas de devices trouves' );
            else{
              foreach ($result['data'] as $device) {
                log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$device['id'] );
                
                log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': trace' );
                $eqLogic = eqLogic::byLogicalId( $device['id'] , 'hydrolinkhome', false) ;
                //log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': '.var_export( $eqLogic , true)  );
                if( ! is_object( $eqLogic ) ){
                  log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Je vais créer le module' );
                  $eqLogic = self::createDevice( $device['id'] ) ;
                  $return['new']++ ;
                }
                else
                  log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Module '.$device['id'].' présent' );
       
                $eqLogic->setName( $device['thing_name'] );
                
                $eqLogic->setConfiguration('system_type_display',$device['system_type_display'] );
                $eqLogic->setConfiguration('image_url'          ,$device['image_url'] );
                $eqLogic->setConfiguration('user'               ,$device['user']['first_name'].' '.$device['user']['last_name'] );
                $eqLogic->setConfiguration('location'           ,$device['location'] );
                //$eqLogic->setConfiguration('is_online'          ,$device['is_online'] );
                
                $eqLogic->setConfiguration('wifi_ssid'          ,$device['properties']['wifi_ssid']['value'] );

                $eqLogic->save();

                // Maj des données
                //$eqLogic->updateDeviceCmd( $device['id'] , $device ) ;
                $eqLogic->updateDeviceCmd( $device['id'] ) ;
                
              }
            }
        }
    
    	return $return['new'] ;
  }
  
  public static function createDevice( $deviceId ){
    		log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$deviceId );
            $eqLogic = eqLogic::byLogicalId( $deviceId , 'hydrolinkhome', false);
            if (! is_object($eqLogic)) {   /// Creation des devices inexistants
              	// RISQUE si logcid <>n mais name identique
                $eqLogic = new hydrolinkhome();
              	$eqLogic->setEqType_name('hydrolinkhome');
              	$eqLogic->setLogicalId( $deviceId );         
              	$eqLogic->setName( $deviceId );
              	$eqLogic->setIsVisible(1);
                $eqLogic->setIsEnable(1);
              	$eqLogic->save();
            }
            else{
              log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Device '.$deviceId.' deja créé' );
            }

    
    return $eqLogic ;
  }
  
  public function updateDeviceCmd( $deviceId , $data = null ){
    
              // bla bla
            if( $data == null ){
                $result = api_hydrolinkhome::getDetail( $deviceId ) ;
              	$data = $result['device'] ;
            }
    
    if( !isset( $data['properties']['_internal_is_online'] ) ){
      log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': json invalide' );
      return false ;
    }
      
    
    
    		log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $data '.$data['system_type'] );
    
      /*          return array(   "out_of_salt_estimate_days" => array('Recharger Sel', 'info', 'numeric', "jours", 0, "GENERIC_INFO", 'jauge', 'jauge', 'A',1,365),
			"gallons_used_today" => array('Ce jour', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'B',3.785,1000),
                        "avg_daily_use_gals" => array('Moyenne', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'C',3.785,1000),
			"salt_level_tenths" => array('Niveau Sel', 'info', 'numeric', "/10", 0, "GENERIC_INFO", 'jauge', 'jauge', 'D',0.1,10),
			"treated_water_avail_gals" => array('Eau Disponible','info','numeric',"litres",0,"GENERIC_INFO",'jauge','jauge','E',3.785,4000),
			"connection_status" => array('Connection','info','binary','',1,"GENERIC_INFO",'badge','badge','F',0,0),
			"regen_status_enum" => array('Commande', 'action', 'select', "", 0, "GENERIC_ACTION", '', '', '1|'.__('progammer une régénération',__FILE__).';2|'.__('Régénérer maintenant',__FILE__))
    */
    
        
    

    
            // bla bla
            if( isset ($data['properties']['gallons_used_today']['value']) )
                $this->checkAndUpdateCmd('gallons_used_today', $data['properties']['gallons_used_today']['value'] * 3.785 );
    
            // bla bla
            if( isset ($data['properties']['avg_daily_use_gals']['value']) )
                $this->checkAndUpdateCmd('avg_daily_use_gals', $data['properties']['avg_daily_use_gals']['value'] * 3.785 );
    
            // bla bla
            if( isset ($data['properties']['salt_level_tenths']['value']) )
                $this->checkAndUpdateCmd('salt_level_tenths', $data['properties']['salt_level_tenths']['value'] * 0.1 );
    
            // bla bla
            if( isset ($data['properties']['treated_water_avail_gals']['value']) )
                $this->checkAndUpdateCmd('treated_water_avail_gals', $data['properties']['treated_water_avail_gals']['value'] * 3.785 );
    
            // bla bla
            if( isset ($data['properties']['_internal_is_online']['value']) )
                $this->checkAndUpdateCmd('_internal_is_online', $data['properties']['_internal_is_online']['value'] );

    
    
            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['salt_level']['salt_level_percent_rounded']) )
                $this->checkAndUpdateCmd('salt_level_percent_rounded', $data['enriched_data']['water_treatment']['salt_level']['salt_level_percent_rounded'] );
    
            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['salt_level_percent']) )
                $this->checkAndUpdateCmd('salt_level_percent', $data['enriched_data']['water_treatment']['salt_level_percent'] );
    
            // bla bla
            if( isset ($data['properties']['gallons_used_today']['converted_value']) )
                $this->checkAndUpdateCmd('gallons_used_today_converted_value', $data['properties']['gallons_used_today']['converted_value'] );    
     
            // bla bla      avg_daily_use_gals.converted_value
            if( isset ($data['properties']['avg_daily_use_gals']['converted_value']) )
                $this->checkAndUpdateCmd('avg_daily_use_gals_converted_value', $data['properties']['avg_daily_use_gals']['converted_value'] );
    
    
    
    /*
    water_treatment.total_water_used.value
water_treatment.treated_water_available.value
current_water_flow_gpm.converted_value
out_of_salt_estimate_days.value
water_treatment.days_since_last_recharge
water_treatment.total_recharges
water_treatment.regeneration_status

gallons_used_today.updated_at
    
    */
    
            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['total_water_used']['value']) )
                $this->checkAndUpdateCmd('water_treatment_total_water_used_value', $data['enriched_data']['water_treatment']['total_water_used']['value'] );

            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['treated_water_available']['value']) )
                $this->checkAndUpdateCmd('treated_water_available_value', $data['enriched_data']['water_treatment']['treated_water_available']['value'] );

            // bla bla
            if( isset ($data['properties']['current_water_flow_gpm']['converted_value']) )
                $this->checkAndUpdateCmd('current_water_flow_gpm_converted_value', $data['properties']['current_water_flow_gpm']['converted_value'] );

            // bla bla
            if( isset ($data['properties']['out_of_salt_estimate_days']['value']) )
                $this->checkAndUpdateCmd('out_of_salt_estimate_days_value', $data['properties']['out_of_salt_estimate_days']['value'] );

            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['days_since_last_recharge']) )
                $this->checkAndUpdateCmd('days_since_last_recharge', $data['enriched_data']['water_treatment']['days_since_last_recharge'] );

            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['total_recharges']) )
                $this->checkAndUpdateCmd('total_recharges', $data['enriched_data']['water_treatment']['total_recharges'] );

            // bla bla
            if( isset ($data['enriched_data']['water_treatment']['regeneration_status']) )
                $this->checkAndUpdateCmd('regeneration_status', $data['enriched_data']['water_treatment']['regeneration_status'] );

            // bla bla
            if( isset ($data['properties']['gallons_used_today']['updated_at']) )
                $this->checkAndUpdateCmd('gallons_used_today_updated_at', $data['properties']['gallons_used_today']['updated_at'] );
    
  }

   
/*"email": self.entry.data[CONF_USERNAME],
            "password": self.entry.data[CONF_PASSWORD]*/
    
  
  
  
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
            $refresh = new hydrolinkhomeCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
            $refresh->setLogicalId('refresh');
            $refresh->setType('action');
            $refresh->setSubType('other');
            $refresh->setEqLogic_id($this->getId());
            $refresh->setIsHistorized(0);
            $refresh->setIsVisible(1);
            $refresh->save();
        }
    
    
    /*
            return array(   "out_of_salt_estimate_days" => array('Recharger Sel', 'info', 'numeric', "jours", 0, "GENERIC_INFO", 'jauge', 'jauge', 'A',1,365),
			"gallons_used_today" => array('Ce jour', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'B',3.785,1000),
                        "avg_daily_use_gals" => array('Moyenne', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'C',3.785,1000),
			"salt_level_tenths" => array('Niveau Sel', 'info', 'numeric', "/10", 0, "GENERIC_INFO", 'jauge', 'jauge', 'D',0.1,10),
			"treated_water_avail_gals" => array('Eau Disponible','info','numeric',"litres",0,"GENERIC_INFO",'jauge','jauge','E',3.785,4000),
			"connection_status" => array('Connection','info','binary','',1,"GENERIC_INFO",'badge','badge','F',0,0),
			"regen_status_enum" => array('Commande', 'action', 'select', "", 0, "GENERIC_ACTION", '', '', '1|'.__('progammer une régénération',__FILE__).';2|'.__('Régénérer maintenant',__FILE__))
    
    */

    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'gallons_used_today');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Ce jour', __FILE__));
            $cmd->setLogicalId('gallons_used_today');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			$cmd->setDisplay('invertBinary',0);
            $cmd->setConfiguration('maxValue',1000);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'avg_daily_use_gals');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Moyenne', __FILE__));
            $cmd->setLogicalId('avg_daily_use_gals');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			//$cmd->setDisplay('invertBinary',0);
            $cmd->setConfiguration('maxValue',1000);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'salt_level_tenths');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Niveau Sel', __FILE__));
            $cmd->setLogicalId('salt_level_tenths');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('/10');
			$cmd->setDisplay('invertBinary',0);
            $cmd->setConfiguration('maxValue',10);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'treated_water_avail_gals');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Eau Disponible', __FILE__));
            $cmd->setLogicalId('treated_water_avail_gals');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			$cmd->setDisplay('invertBinary',0);
            $cmd->setConfiguration('maxValue',4000);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, '_internal_is_online');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('_internal_is_online', __FILE__));
            $cmd->setLogicalId('_internal_is_online');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('/10');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',4000);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
            /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'regen_status_enum');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Regénérer', __FILE__));
            $cmd->setLogicalId('regen_status_enum');
            $cmd->setType('action');
            $cmd->setSubType('select');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('/10');
			//$cmd->setDisplay('invertBinary',1);
            //$cmd->setConfiguration('maxValue',4000);
			$cmd->setDisplay('generic_type', 'GENERIC_ACTION');
          	$cmd->setConfiguration('listValue', '1|'.__('progammer une régénération',__FILE__).';2|'.__('Régénérer maintenant',__FILE__) );
          	//$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }


    
    
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'salt_level_percent_rounded');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT salt_level_percent_rounded', __FILE__));
            $cmd->setLogicalId('salt_level_percent_rounded');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'salt_level_percent');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT salt_level_percent', __FILE__));
            $cmd->setLogicalId('salt_level_percent');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'gallons_used_today_converted_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT gallons_used_today.converted_value', __FILE__));
            $cmd->setLogicalId('gallons_used_today_converted_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'avg_daily_use_gals_converted_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT avg_daily_use_gals_converted_value', __FILE__));
            $cmd->setLogicalId('avg_daily_use_gals_converted_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'water_treatment_total_water_used_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT water_treatment_total_water_used_value', __FILE__));
            $cmd->setLogicalId('water_treatment_total_water_used_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'treated_water_available_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT treated_water_available_value', __FILE__));
            $cmd->setLogicalId('treated_water_available_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'current_water_flow_gpm_converted_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT current_water_flow_gpm_converted_value', __FILE__));
            $cmd->setLogicalId('current_water_flow_gpm_converted_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'out_of_salt_estimate_days_value');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT out_of_salt_estimate_days_value', __FILE__));
            $cmd->setLogicalId('out_of_salt_estimate_days_value');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('jours');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'days_since_last_recharge');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT days_since_last_recharge', __FILE__));
            $cmd->setLogicalId('days_since_last_recharge');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	$cmd->setUnite('litres');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'total_recharges');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT total_recharges', __FILE__));
            $cmd->setLogicalId('total_recharges');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'regeneration_status');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT regeneration_status', __FILE__));
            $cmd->setLogicalId('regeneration_status');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        } 
    
        /// Creation de la commande info mode (correspond à l'état sous forme d'une chaine de carcateres)
        $cmd = $this->getCmd(null, 'gallons_used_today_updated_at');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('ZEIT gallons_used_today_updated_at', __FILE__));
            $cmd->setLogicalId('gallons_used_today_updated_at');
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setEqLogic_id($this->getId());
          	//$cmd->setUnite('%');
			//$cmd->setDisplay('invertBinary',0);
            //$cmd->setConfiguration('maxValue',100);
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
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