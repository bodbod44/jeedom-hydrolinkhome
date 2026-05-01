<?php
  
class api_hydrolinkhome {

  	public static $bouchon = true ;
      
  //* Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function getURL(){
    	$region = config::byKey('region',__CLASS__,'com');
    	return 'https://app.hydrolinkhome.'.$region ;
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
    	$region = config::byKey('region',__CLASS__,'com');
    
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
                    'Origin: '.$this->getURL() ,
                    'Referer: '.$this->getURL().'/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'X-Requested-With: XMLHttpRequest'
            ),
            CURLOPT_URL => $this->getURL().'/v1/auth/login',
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


        if( self::$bouchon ){
      		return json_decode( bouchons::getBouchon( 'devices' ) , true) ;
        }
    	else
          	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': PAS DE BOUCHON' );
    
        /// Preparation de la requete : json
    	$token = config::byKey('access_token',__CLASS__,'');
        $region = config::byKey('region',__CLASS__,'com');
    	$data = json_encode( array('email' => $User, 'Authorization' => $token) ) ;

        /// Parametres cUrl
        $params = array(
            //CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    'Origin: '.self::getURL(),
                    'Referer: '.self::getURL().'/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
              		'Authorization: Bearer '.$token
            ),
            CURLOPT_URL => self::getURL().'/v1/devices?all=false&per_page=200',
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
  public static function getDetail( $DeviceId  ){
    //https://api.hydrolinkhome.eu/v1/devices/{self.device_id}/live"


        if( self::$bouchon ){
      		return json_decode( bouchons::getBouchon( 'detail' , $DeviceId ) , true) ;
        }
    	else
          	log::add(__CLASS__, 'debug',  __METHOD__.'(ln '.__LINE__.')'.': PAS DE BOUCHON' );
    
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
                    'Origin: '.self::getURL() ,
                    'Referer: '.self::getURL().'/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'X-Requested-With: XMLHttpRequest',
              		'Authorization: Bearer '.$token
            ),
            CURLOPT_URL => self::getURL().'/v1/devices/'.$DeviceId.'/live',
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
    
  
} // Fin class api_hydrolinkhome