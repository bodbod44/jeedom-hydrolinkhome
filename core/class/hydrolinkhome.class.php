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

        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );

        api_hydrolinkhome::Login() ;
        $result = api_hydrolinkhome::getList() ;
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': test='.var_export( $result , true) );
    }
  
    //* Fonction de synchronisation
    //* Déclencher manuellement et toutes les x minutes
    //* Elle appelle l'api pour ramener tous les appareils. Elle créée l'appereil si non existant
    //* Elle met à jour les commandes
    public static function synchronise( $manuel = false ){
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );

        $result = api_hydrolinkhome::getList() ;

        $return['new'] = 0 ;
        $return['update'] = 0 ;
        $return['delete'] = 0 ;

        if( $result != false ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $result[total]='.$result['total'] );
            if( $result['total'] == 0)
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Pas de devices trouves' );
            else{
                foreach ($result['data'] as $device) {
                    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$device['id'].'-'.$device['image_url'] );
                  
                    $eqLogic = eqLogic::byLogicalId( $device['id'] , 'hydrolinkhome', false) ;
                    if( ! is_object( $eqLogic ) ){
                        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Je vais créer le module' );
                        $eqLogic = self::createDevice( $device['id'] ) ;
                        $return['new']++ ;
                    }
                    else{
                        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Module '.$device['id'].' présent' );
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

                    if( $manuel )
                        $eqLogic->CreateCmds( $device['id'] , $device ) ;
                  
                    // Maj des données
                    //$eqLogic->updateDeviceCmd( $device['id'] , $device ) ;
                    $eqLogic->updateDeviceCmd( $device ) ;
                } // for
            } // result == 0
        }

        return $return ;
    }

    //* Fonction de création des nouveaux appareils
    //* Appellée à chaque synchro
    public static function createDevice( $deviceId ){
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $device='.$deviceId );
        $eqLogic = eqLogic::byLogicalId( $deviceId , 'hydrolinkhome', false);
        if (! is_object($eqLogic)) {   /// Creation des devices inexistants
            // RISQUE si logcid <>n mais name identique
            $eqLogic = new hydrolinkhome();
            $eqLogic->setEqType_name('hydrolinkhome');
            $eqLogic->setLogicalId( $deviceId );         
            $eqLogic->setName( $deviceId );
            $eqLogic->setIsVisible(1);
            $eqLogic->setIsEnable(1);
            $eqLogic->setCategory('heating', 1);
            $eqLogic->save();
            log::add('hydrolinkhome', 'info', '1 nouveau module HydroLink Home ajouté ('.$deviceId.')');
        }
        else{
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Device '.$deviceId.' deja créé' );
        }
        return $eqLogic ;
    }
    
    /**
     * @brief Fonction qui permet de créer une commande dont le nom logique est passé en parametre
     */
//class hydrolinkhome extends eqLogic
    public function CreateCmds( $deviceId , $data ) { 
        $json = file_get_contents(__DIR__.'/_Commands.json');
        if ( $json === false ){
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.'): JSON _Commands.json non trouvé' );
            return false ;
        }
      
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): $id = '.$data['id'] );
        
        // On parcours tous les commandes du json
        $tab_cmds = json_decode($json, true);
        if( $tab_cmds === false ) return false ;
        foreach ( $tab_cmds as $commande) {
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): $commande = '.$commande['Name'].' - '.$commande['LogicalId'].' - '.$commande['Config_JsonElement'] );
            
            if(   outils::getJSONElementByName( $commande['Config_JsonElement'] , $data ) !== 'mon_element_json_non_trouve'
               || $commande['Type'] == 'action' ){ // verifie que le contenu de la commande est présent dans le JSON
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): $commande = '.$commande['Name'].' type action ou trouvée dans le JSON. On va créer la cmd' );
                //$val = outils::getJSONElementByName( $commande['Config_Param2'] , $data ) ;
                $this->CreateCmd( $commande['LogicalId'] ) ;
            }
            else{
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): $commande = '.$commande['Name'].' non trouvé dans le JSON. Pas de création de cmd' );
            }
        } //foreach      
    }
    
    /**
     * @brief Fonction qui permet de créer une commande dont le nom logique est passé en parametre
     */
//class hydrolinkhome extends eqLogic
    public function CreateCmd( $commande = '' , $MajOrder = false , $MajName = false ) {  
      
        if( $commande == '' Or $commande == null){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande vide' );
            return false ;
        }
      
        $json = file_get_contents(__DIR__.'/_Commands.json');
        if ( $json === false ){
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.')'.': JSON _Commands.json non trouvé' );
            return false ;
        }
      
        $tab_cmds = json_decode($json, true);
        if( $tab_cmds === false ) return false ;

        //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Recherche de la commande '.$commande.'...' );
        $cmd = $this->getCmd( null, $tab_cmds[$commande]['LogicalId'] );
        if (!is_object($cmd)) {
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande '.$commande.' non trouvé. On va créer' );
            $cmd = new hydrolinkhomeCmd();

            $cmd->setLogicalId( $tab_cmds[$commande]['LogicalId'] );
            $cmd->setName(  __( $tab_cmds[$commande]['Name'] , __FILE__));
            $cmd->setOrder(     $tab_cmds[$commande]['Order'] );
            $cmd->setType(      $tab_cmds[$commande]['Type'] );
            $cmd->setSubType(   $tab_cmds[$commande]['SubType'] );

            if($tab_cmds[$commande]['Unite']                   !== null) $cmd->setUnite( $tab_cmds[$commande]['Unite'] );
            
            if($tab_cmds[$commande]['Config_infoName']         !== null) $cmd->setConfiguration('infoName'   , $tab_cmds[$commande]['Config_infoName']   );
            if($tab_cmds[$commande]['Config_value']            !== null) $cmd->setConfiguration('value'      , $tab_cmds[$commande]['Config_value']      );
            if($tab_cmds[$commande]['Config_minValue']         !== null) $cmd->setConfiguration('minValue'   , $tab_cmds[$commande]['Config_minValue']   );
            if($tab_cmds[$commande]['Config_maxValue']         !== null) $cmd->setConfiguration('maxValue'   , $tab_cmds[$commande]['Config_maxValue']   );
            if($tab_cmds[$commande]['Config_listValue']        !== null) $cmd->setConfiguration('listValue'  , $tab_cmds[$commande]['Config_listValue']  );
            if($tab_cmds[$commande]['Config_JsonElement']      !== null) $cmd->setConfiguration('JsonElement', $tab_cmds[$commande]['Config_JsonElement']);
            //if($tab_cmds[$commande]['Config_Param2']           !== null) $cmd->setConfiguration('Param2'     , $tab_cmds[$commande]['Config_Param2']     );
            //if($tab_cmds[$commande]['Config_Param3']           !== null) $cmd->setConfiguration('Param3'     , $tab_cmds[$commande]['Config_Param3']     );
            //if($tab_cmds[$commande]['Config_Param4']           !== null) $cmd->setConfiguration('Param4'     , $tab_cmds[$commande]['Config_Param4']     );
            //if($tab_cmds[$commande]['Config_Param5']           !== null) $cmd->setConfiguration('Param5'     , $tab_cmds[$commande]['Config_Param5']     );
            if($tab_cmds[$commande]['CoefMultip']              !== null) $cmd->setConfiguration('CoefMultip' , $tab_cmds[$commande]['CoefMultip']        );
            
            if($tab_cmds[$commande]['setValue']                !== null) $cmd->setValue( $this->getCmd( null, $tab_cmds[$commande]['setValue'] )->getId() ) ;
            
            if($tab_cmds[$commande]['setDisplay_param_step']            !== null) $cmd->setDisplay('parameters'           , ['step' => $tab_cmds[$commande]['setDisplay_param_step'] ]);
            if($tab_cmds[$commande]['setDisplay_invertBinary']          !== null) $cmd->setDisplay('invertBinary'         , $tab_cmds[$commande]['setDisplay_invertBinary']           );
            if($tab_cmds[$commande]['setDisplay_forceReturnLineBefore'] !== null) $cmd->setDisplay('forceReturnLineBefore', $tab_cmds[$commande]['setDisplay_forceReturnLineBefore']  );
                        
            if($tab_cmds[$commande]['setgeneric_type']         !== null) $cmd->setGeneric_type( $tab_cmds[$commande]['setgeneric_type'] );
              
            if($tab_cmds[$commande]['setTemplate_dashboard']   !== null) $cmd->setTemplate('dashboard', $tab_cmds[$commande]['setTemplate_dashboard'] );
            if($tab_cmds[$commande]['setTemplate_mobile'   ]   !== null) $cmd->setTemplate('mobile'   , $tab_cmds[$commande]['setTemplate_mobile'   ] );
            
            //$cmd->setEventOnly(1); // obselete 4.2

            $cmd->setEqLogic_id(   $this->getId() );
            $cmd->setIsHistorized( $tab_cmds[$commande]['IsHistorized'] );
            $cmd->setIsVisible(    $tab_cmds[$commande]['IsVisible'] );
            $cmd->save();

            return true ;
        } // !is_object($cmd)
        else{
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande '.$commande.' existante' );
            if( $MajOrder ){
                $cmd->setOrder( $tab_cmds[$commande]['Order'] );
                $cmd->save();
            }
            if( $MajName ){
                $cmd->setName(  __( $tab_cmds[$commande]['Name'] , __FILE__));
                $cmd->save();
            }
        }
        return true ;
    }

    //* Fonction permettant de mettre à jour les infos issus du json
    public function updateDeviceCmd( $data = null ){
        $cmds = $this->getCmd('info',null, true,true); // parcours toutes cmd info
        if (sizeof($cmds) > 0) {
            foreach($cmds as $cmd) { // parcours toutes cmd info
                //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande='.$cmd->getName().' - '.$cmd->getlogicalId().'-'.$cmd->getConfiguration('JsonElement') );
                $val = outils::getJSONElementByName( $cmd->getConfiguration('JsonElement') , $data ) ;//JsonElement
                if( $val !== 'mon_element_json_non_trouve' ){
                    // element trouvé dans le json
                    
                    if( $cmd->getConfiguration('CoefMultip', null) !== null ){
                        //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande='.$cmd->getName().' - Coef trouvé ('.$cmd->getConfiguration('CoefMultip').') - '.$val.' - '.((float)$val * (float)$cmd->getConfiguration('CoefMultip')) );
                        $this->checkAndUpdateCmd( $cmd->getlogicalId() , (float)$val * (float)$cmd->getConfiguration('CoefMultip') );
                    }
                    else{
                        //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Commande='.$cmd->getName().' - Coef non trouvé' );
                        $this->checkAndUpdateCmd( $cmd->getlogicalId() , $val );
                    }
                }
            }
        }
    }  
  
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {
        // Mise à jour des commandes infos
        $refresh_freq = config::byKey('refresh_freq','hydrolinkhome','10') ; // Toutes les 10 min par défaut si non parametré
        if( $refresh_freq > 0 ){ // Si param != off
            if( (date("i") % $refresh_freq ) == 0 ){ // Si on tombe bien sur le x minute
                if( date( "H:i" ) == '00:00' ){
                    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Refresh commandes (true)...' );	
                    self::synchronise( true ) ;
                }
                else{
                    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': Refresh commandes ()...' );	
                    self::synchronise() ;
                }
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
        if( $aujourdhui > $cible && (date('w', $aujourdhui )) == '0' ){ //0=dimanche
         
            // On cherche le plugin 'hydrolinkhome' pour vérifier l'origine de l'installation
            foreach (update::all() as $update) {
                if ($update->getLogicalId() == 'hydrolinkhome'){
                    if( $update->getSource()  != 'market' ){
                        message::add('hydrolinkhome', 'Votre plugin HydroLink Home a été installé depuis une version autre que le market (github ou fichier). Nous préconisons l\'installation depuis le market pour profiter au mieux du support. Merci' );
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
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.' : Commande execute : '.$this->getEqLogic()->getName().' - '.$this->getType().' - '.$this->getLogicalId().' ('.$this->getId().')');  
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $_options='.var_export( $_options ,true) );
      
        if( $this->getType() == 'info'){

        }
        
        if( $this->getType() == 'action'){
            if( $this->getLogicalId() == 'refresh'){
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': '.$this->getLogicalId() );              
              
                $result = api_hydrolinkhome::getDetail( $this->getEqLogic()->getLogicalId() ) ;
                if( $result === false ){
                    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.' : Execute KO : '.$this->getEqLogic()->getName().' - '.$this->getType().' - '.$this->getLogicalId().' ('.$this->getId().')');    
                }
            }
            
            if( $this->getLogicalId() == 'regen_status_enum'){
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': '.$this->getLogicalId().' ('.$_options['select'].')' );  
                //$result = api_hydrolinkhome::getDetail( $deviceId )
            }
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}