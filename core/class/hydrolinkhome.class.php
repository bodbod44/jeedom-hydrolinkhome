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

    //* Fonction de test
    public static function test(){

        //https://github.com/Roeli1996/ha-ecowater-hydrolink/blob/5ffbdd550d477a2a465dff33f7c0d0ef7126f40f/custom_components/ecowater_hydrolink_custom/const.py

        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );

        api_hydrolinkhome::Login() ;
        $result = api_hydrolinkhome::getList() ;
        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': test='.var_export( $result , true) );
    }
  
    //* Fonction de synchronisation
    //* Déclencher manuellement et toutes les x minutes
    //* Elle appelle l'api pour ramener tous les appareils. Elle créée l'appereil si non existant
    //* Elle met à jour les commandes
    public static function synchronise(){
        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );

        $result = api_hydrolinkhome::getList() ;

        $return['new'] = 0 ;
        $return['update'] = 0 ;
        $return['delete'] = 0 ;

        if( $result != false ){
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $result[total]='.$result['total'] );
            if( $result['total'] == 0)
                log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Pas de devices trouves' );
            else{
                foreach ($result['data'] as $device) {
                    log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$device['id'] );
                    $eqLogic = eqLogic::byLogicalId( $device['id'] , 'hydrolinkhome', false) ;
                    if( ! is_object( $eqLogic ) ){
                        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Je vais créer le module' );
                        $eqLogic = self::createDevice( $device['id'] ) ;
                        $return['new']++ ;
                    }
                    else{
                        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Module '.$device['id'].' présent' );
                        $return['update']++ ;
                    }

                    $eqLogic->setName( $device['thing_name'] );

                    $eqLogic->setConfiguration('system_type_display',$device['system_type_display'] );
                    $eqLogic->setConfiguration('image_url'          ,$device['image_url'] );
                    $eqLogic->setConfiguration('user'               ,$device['user']['first_name'].' '.$device['user']['last_name'] );
                    $eqLogic->setConfiguration('location'           ,$device['location'] );
                    //$eqLogic->setConfiguration('is_online'          ,$device['is_online'] );

                    $eqLogic->setConfiguration('wifi_ssid'          ,$device['properties']['wifi_ssid']['value'] );

                    $eqLogic->save();

                    // Maj des données
                    $eqLogic->updateDeviceCmd( $device['id'] , $device ) ;
                } // for
            } // result == 0
        }

        return $return ;
    }

    //* Fonction de création des nouveaux appareils
    //* Appellée à chaque synchro
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
            log::add('heatzy', 'info', '1 nouveau module HydroLink Home ajouté ('.$deviceId.')');
        }
        else{
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Device '.$deviceId.' deja créé' );
        }
        return $eqLogic ;
    }
  
    //* Fonction permettant de mettre à jour les infos issus du json
    public function updateDeviceCmd( $deviceId , $data = null ){
    
        // Si pas de données fournies, on va les cherchers pour le deviceId (getDetail)
        if( $data == null ){
            $result = api_hydrolinkhome::getDetail( $deviceId ) ;
            $data = $result['device'] ;
        }
    
        // On vérifier si le tbleau possede au moins la donnée $data['properties']['_internal_is_online']
        if( !isset( $data['properties']['_internal_is_online'] ) ){
            log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': json invalide' );
            return false ;
        }

        log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $data '.$data['system_type'] );

    
        // bla bla
        $this->checkAndUpdateCmdIsset('gallons_used_today', $data['properties']['gallons_used_today']['value'] * 3.785 );

        // bla bla
        $this->checkAndUpdateCmdIsset('avg_daily_use_gals', $data['properties']['avg_daily_use_gals']['value'] * 3.785 );

        // bla bla
        $this->checkAndUpdateCmdIsset('salt_level_tenths', $data['properties']['salt_level_tenths']['value'] * 0.1 );

        // bla bla
        $this->checkAndUpdateCmdIsset('treated_water_avail_gals', $data['properties']['treated_water_avail_gals']['value'] * 3.785 );

        // bla bla
        $this->checkAndUpdateCmdIsset('_internal_is_online', $data['properties']['_internal_is_online']['value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('salt_level_percent_rounded', $data['enriched_data']['water_treatment']['salt_level']['salt_level_percent_rounded'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('salt_level_percent', $data['enriched_data']['water_treatment']['salt_level_percent'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('gallons_used_today_converted_value', $data['properties']['gallons_used_today']['converted_value'] );    
 
        // bla bla
        $this->checkAndUpdateCmdIsset('avg_daily_use_gals_converted_value', $data['properties']['avg_daily_use_gals']['converted_value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('water_treatment_total_water_used_value', $data['enriched_data']['water_treatment']['total_water_used']['value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('treated_water_available_value', $data['enriched_data']['water_treatment']['treated_water_available']['value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('current_water_flow_gpm_converted_value', $data['properties']['current_water_flow_gpm']['converted_value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('out_of_salt_estimate_days_value', $data['properties']['out_of_salt_estimate_days']['value'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('days_since_last_recharge', $data['enriched_data']['water_treatment']['days_since_last_recharge'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('total_recharges', $data['enriched_data']['water_treatment']['total_recharges'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('regeneration_status', $data['enriched_data']['water_treatment']['regeneration_status'] );

        // bla bla
        $this->checkAndUpdateCmdIsset('gallons_used_today_updated_at', $data['properties']['gallons_used_today']['updated_at'] );
    
  }
  
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public function checkAndUpdateCmdIsset( $cmd , $data ) {
        if( isset($data) )
            $this->checkAndUpdateCmd( $cmd , $data );
    }
  
  
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {
        // Mise à jour des commandes infos
        $refresh_freq = config::byKey('refresh_freq',__CLASS__,'10') ; // Toutes les 10 min par défaut si non parametré
        if( $refresh_freq > 0 ){ // Si param != off
            if( (date("i") % $refresh_freq ) == 0 ){ // Si on tombe bien sur le x minute
                log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Refresh commande...' );
                self::synchronise() ;
            }
        }
    }
  

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

  
    //* Fonction exécutée automatiquement tous les jours par Jeedom
    public static function cronDaily() {        
        $aujourdhui =  strtotime( date(  "Y-m-d H:i:s" ) ) ;
        $cible = strtotime( "2026-07-01 00:00:00" ) ;         
        if( $aujourdhui > $cible && (date('w', $aujourdhui )) == '0' ){
         
            // On cherche leplugin heatzy pour vérifier l'origine de l'installation
            foreach (update::all() as $update) {
                if ($update->getLogicalId() == 'heatzy'){
                    if( $update->getSource()  != 'market' ){
                        message::add("Heatzy", 'Votre plugin HydroLink Home a été installé depuis une version autre que le market (github ou fichier). La version officielle du plugin HEATZY a été mise à jour sur le market. Je vous invite à aller sur le market et réinstaller le pugin HEATZY. Votre configuration (compte, appareils et commandes) sera conservée. Merci' );
                        break;
                    } //if
                } //if
            } //foreach
        } //if      
    }
  
  
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
        $cmd = $this->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            $cmd = new hydrolinkhomeCmd();
            $cmd->setName(__('Rafraichir', __FILE__));
            $cmd->setLogicalId('refresh');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
        
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
            $cmd->setTemplate('dashboard', 'gauge');
            $cmd->setTemplate('mobile', 'gauge');
          	$cmd->setIsHistorized(0);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande action 
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
    
        /// Creation de la commande info 
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
    
        /// Creation de la commande info 
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info 
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info 
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info
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
    
        /// Creation de la commande info 
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
    //* Permet de crypter/décrypter automatiquement des champs de configuration des équipements
    //* Exemple avec le champ "Mot de passe" (password)
    public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
    }
    public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
    }*/


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
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.' : Commande execute : '.$this->getEqLogic()->getName().' - '.$this->getLogicalId().' ('.$this->getId().')');  
      log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $_options='.var_export( $_options ,true) );
    }

    /*     * **********************Getteur Setteur*************************** */
}