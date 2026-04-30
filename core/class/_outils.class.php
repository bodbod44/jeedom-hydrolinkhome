<?php
  
class outils {

    public static function LireJSON( $json_name ) {
      	log::add('hydrolinkhome', 'debug',  __METHOD__.'(ln '.__LINE__.') '.__DIR__.'/'.$json_name ) ;
        $json = file_get_contents(__DIR__.'/'.$json_name) ;
        if( $json ) {
            if( !json_decode( $json , true ) ){
                log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.') JSON invalide - Problème decode '.$json_name ) ;
                return false ;
            }
          	return $json ;
        }
        else{
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.') Problème lecture '.$json_name ) ;
            return false ;
        }
    }

  
  
} // Fin class bouchons