<?php
  
class bouchons {
     /* @brief Fonction qui permet de synchroniser
     *        
     * @return false en cas d'erreur le nombre de modules synchroniser       
     */
//class Synchro
    public static function getBouchon( $fonction , $data = null , $format = 'json' ) {
      
      
      switch ($fonction){
        case 'login':
            return outils::LireJSON( 'bouchons/login.json' ) ;
            break;
        case 'devices':
            return outils::LireJSON( 'bouchons/devices.json' ) ;
            break;
        case 'detail':
            return outils::LireJSON( 'bouchons/detail-or-summary_'.$data.'.json' ) ;
            break;
        default:
            log::add('hydrolinkhome', 'error',  __METHOD__.'(ln '.__LINE__.') fonction inconnu ('.$fonction.')' ) ;
            return false ;
		}
    }

  
  
} // Fin class bouchons