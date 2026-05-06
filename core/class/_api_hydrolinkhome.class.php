<?php
  
class api_hydrolinkhome {

    public static $bouchon = false ;
    
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function getURL(){
        $region = config::byKey('region','hydrolinkhome','eu');
        return 'https://api.hydrolinkhome.'.$region ;
    }
  
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function Login(){
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );
    
        if( self::$bouchon )
            return bouchons::getBouchon( 'login') ;
  
    	$User = config::byKey('email','hydrolinkhome','');
    	$Passwd = config::byKey('password','hydrolinkhome','');
    	$region = config::byKey('region','hydrolinkhome','');
    
        if( empty( $User ) || empty( $Passwd ) || empty( $region ) ){
            log::add('hydrolinkhome', 'error',  'Vérifiez que vous avez valorisé et sauvegardé vos informations : email + password + region' );
            return false ;
        }
    
        /// Preparation de la requete : json
        //$data = json_encode( array('email' => $User, 'password' => $Passwd, 'lang' => $Lang) ) ;
        $data = json_encode( array('email' => $User, 'password' => $Passwd) ) ;
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $data='.$data );

        /// Parametres cUrl
        $params = array(
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    //'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json'
                    //'Origin: '.self::getURL() ,
                    //'Referer: '.self::getURL().'/',
                    //'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    //'X-Requested-With: XMLHttpRequest'
            ),
            CURLOPT_URL => self::getURL().'/v1/auth/login',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $data
        );

        /// Initialisation de la ressources curl
        $hydrolinkHandle = curl_init();
        if ($hydrolinkHandle === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($hydrolinkHandle, $params);
        
        /// Excute la requete
        $result = curl_exec($hydrolinkHandle);

        /// Test le code retour http
        $httpcode = curl_getinfo($hydrolinkHandle, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($hydrolinkHandle);
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $params='.var_export( $params , true) );

        if( $httpcode != 200 && $httpcode != 400 ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode.' - '.$result );
            return false;
        }
        
        ///Décodage de la réponse
        $aRep = json_decode($result, true);
    
        if( isset( $aRep['access_token'] ) ){
            config::save('access_token'   , $aRep['access_token' ]  , 'hydrolinkhome');
            config::save('refresh_token'  , $aRep['refresh_token']  , 'hydrolinkhome');
            config::save('user_id'        , $aRep['user_id']        , 'hydrolinkhome');
        }
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return true;
    }
  
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function getList( $Recurrence = 0 ){
        //https://api.hydrolinkhome.eu/v1/devices?all=false&per_page=200
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': ' );

        if( self::$bouchon ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': MODE BOUCHON' );
            return json_decode( bouchons::getBouchon( 'devices' ) , true) ;
        }
        //else
        //    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': PAS DE BOUCHON' );
    
        /// Preparation de la requete : json
        $token = config::byKey('access_token','hydrolinkhome','');
        $region = config::byKey('region','hydrolinkhome','');
    
        if( empty($token) ){
            if( self::Login() == false)
                return false ;
            $token = config::byKey('access_token','hydrolinkhome',null);
        }
    
        if( empty($token) || empty($region) ){
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.')'.': region ou token non valorisé ($region='.$region.' - $token='.$token.')' );
            return false;
        }
    
        //$data = json_encode( array('email' => $User, 'Authorization' => $token) ) ;

        /// Parametres cUrl
        $params = array(
            //CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    //'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    //'Origin: '.self::getURL(),
                    //'Referer: '.self::getURL().'/',
                    //'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
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
        $hydrolinkHandle = curl_init();
        if ($hydrolinkHandle === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($hydrolinkHandle, $params);
        
        /// Excute la requete
        $result = curl_exec($hydrolinkHandle);

        /// Test le code retour http
        $httpcode = curl_getinfo($hydrolinkHandle, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($hydrolinkHandle);
    
    	log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $params='.var_export( $params , true) );

        if( $httpcode != 200 ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode.' - '.$result );
          
            if( $Recurrence < 1 && $httpcode == 401 ){
                log::add('hydrolinkhome', 'debug', __METHOD__.'(ln '.__LINE__.')'.': On retente (Recurrence '.$Recurrence.')');
                self::Login() ;
                
                $aRep = self::getList( $Recurrence + 1) ;
                if( $aRep === false ){
                    log::add('hydrolinkhome', 'debug', __METHOD__.'(ln '.__LINE__.')'.':'.$Did.'- Nouvelle tentative KO (Recurrence '.$Recurrence.')');
                    log::add('hydrolinkhome', 'error', 'Erreur lors de l\'appel hydrolink home (erreur '.$httpcode.'). Vérifier vos parametres email, password et region' );
                    return false;
                }
                log::add('hydrolinkhome', 'debug', __METHOD__.'(ln '.__LINE__.')'.':'.$Did.'- Nouvelle tentative OK (Recurrence '.$Recurrence.')');
                $result = json_encode( $aRep );
            }
            else
                return false;
          
        }
        
        ///Décodage de la réponse
        //$aRep = json_decode($result, true);
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return json_decode($result, true);
  
  }
          
    //* Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function getDetail( $DeviceId  ){
    //https://api.hydrolinkhome.eu/v1/devices/{self.device_id}/live"


        if( self::$bouchon ){
            return json_decode( bouchons::getBouchon( 'detail' , $DeviceId ) , true) ;
        }
        else
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': PAS DE BOUCHON' );

        /// Preparation de la requete : json
        $token = config::byKey('access_token','hydrolinkhome','');
        $region = config::byKey('region','hydrolinkhome','');
    
        if( empty($token) ){
            if( self::Login() == false)
                return false ;
            $token = config::byKey('access_token','hydrolinkhome',null);
        }
    
        if( empty($token) || empty($region) ){
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.')'.': region ou token non valorisé ($region='.$region.' - $token='.$token.')' );
            return false;
        }
    
        /// Preparation de la requete : json
        //$data = json_encode( array('email' => $User, 'password' => $Passwd, 'lang' => $Lang) ) ;
        //$data = json_encode( array('email' => $User, 'Authorization' => $token) ) ;

        /// Parametres cUrl
        $params = array(
            //CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                    'Accept : application/json, text/plain, */*',
                    //'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Content-Type: application/json',
                    //'Origin: '.self::getURL() ,
                    //'Referer: '.self::getURL().'/',
                    //'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    //'X-Requested-With: XMLHttpRequest',
                    'Authorization: Bearer '.$token
            ),
            CURLOPT_URL => self::getURL().'/v1/devices/'.$DeviceId.'/live',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10
            //CURLOPT_POSTFIELDS => $data
        );

        /// Initialisation de la ressources curl
        $hydrolinkHandle = curl_init();
        if ($hydrolinkHandle === false)
            return false;
             
        /// Configuration des options
        curl_setopt_array($hydrolinkHandle, $params);
        
        /// Excute la requete
        $result = curl_exec($hydrolinkHandle);

        /// Test le code retour http
        $httpcode = curl_getinfo($hydrolinkHandle, CURLINFO_HTTP_CODE);

        /// Ferme la connexion
        curl_close($hydrolinkHandle);
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': $params='.var_export( $params , true ) );

        if( $httpcode != 200 && $httpcode != 400 ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.': erreur http '.$httpcode );
            return false;
        }
        
        ///Décodage de la réponse
        //$aRep = json_decode($result, true);
    
        log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.')'.':'.$result );
        
        return json_decode($result, true);
    } //public static function getDetail    
  
} // Fin class api_hydrolinkhome