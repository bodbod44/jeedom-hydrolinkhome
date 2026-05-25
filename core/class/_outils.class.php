<?php
  
class outils {

//class outils
    public static function LireJSON( $json_name , $format = 'tab' ) {
        $json = file_get_contents(__DIR__.'/'.$json_name.'.json') ;
        if( $json ) {
            $tab = json_decode( $json , true );
            if( !$tab ){
                log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.') JSON invalide - Problème decode '.$json_name.'.json' ) ;
                return false ;
            }
        }
        else{
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.') Problème lecture '.$json_name.'.json' ) ;
            return false ;
        }        
        
        switch( $format ){
            case 'tab':
                return $tab ;
                break;
            case 'json':
                return $json ;
                break;
            default :
                log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.')'.' : Type de format de sortie inconnu ('.$format.')');
                break;
        } // switch

        return $false ;
    }
    

//class outils
    public static function getJSONElementByName( $element , $data ) { 
    
        if( empty($element) ){
            log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): Chemin JSON non valorisdé dans le fichier des commandes' );
            return 'mon_element_json_non_trouve' ;
        }
            
        /* Methode avec eval mais plus dangereuse
        eval('$val = $data'.$element.';') ;
        return $val ;
        */
      
        // ******************** verifier commande refresh ********************
      
        //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): element='.$element );
      
        if( str_starts_with( $element , '[' ) && str_ends_with( $element , ']' )  ){  // ['xxx']['xxx']['xxx']
            //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): avec [][]' );
            $element = ltrim( $element , '[\'' )                ; $element = ltrim( $element , '[' ) ;
            $element = rtrim( $element , '\']' )                ; $element = rtrim( $element , ']' ) ;
            $element = str_replace( '\'][\'' , '|' , $element ) ; $element = str_replace( '][' , '|' , $element ) ;
            $tab_el = explode( '|' , $element ) ;
        }
        else{    // xxx.xxx.xxx
            //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): avec ...' );
            $tab_el = explode( '.' , $element ) ;
        }
      
        $val = $data ;
        //log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): var_export='.var_export( $tab_el , true) );
        foreach( $tab_el as $el ){
            if( !isset( $val[ $el ] ) ){
                $val = 'mon_element_json_non_trouve' ;
                log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): el='.$el.' NON trouve' );
                break ;
            }
            //else
            //    log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.'): el='.$el.' trouve' );
            $val = $val[ $el ] ;
        } //foreach
        return $val ;      
    }
  
} // Fin class outils