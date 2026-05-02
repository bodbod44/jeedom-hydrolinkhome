# Plugin HydroLink Home (hydrolinkhome)

Ce plugin sert à récupérer les données des adoucisseurs connectés Hydrolink.


* Gestion du plugin :
![gestion](/chemin/access/gestion.png "gestion")
- **Configuration** : Configuration du plugin
- **Documentation** : Lien vers la docuementation du plugin (cette page)
- **Assistance** : Lien permettant d'aller sur le forum directement sur les sujets HydrolinkHome

* Configuration du plugin :
![Configuration](/chemin/access/Configuration.png "Configuration")
- **Email** : Email du compte HydroLink Home
- **Mot de passe** : Mot de passe du compte HydroLink Home
- **Region** : Region d'appartenance du compte (https://hydrolinkhome.**EU** ou https://hydrolinkhome.**COM**)
- **Frequence de rafaichissement** : Fréquence à laquelle le plugin ira récupérer la mise à jour des informations sur le serveur HydroLink (10 min par défaut)
- **Synchronisation** : Bouton premettant de synchroniser manuellement le plugin avec le compte Hydrolink (récupération des appareils, mise à jour des informations)

* Affichage des appareils :
![MesHydrolinkHome](/chemin/access/MesHydrolinkHome.png "MesHydrolinkHome")
Tous les appareils rattachés au compet seront affichés ici

* Parametres des appareils :
![parametres](/chemin/access/parametres.png "parametres")
- **Identifiant de l'appareil** : Identifiant technique de l'appareil. Il ne peut pas être modifié
- **Description** : Champ libre de saisie
- **Date création** : Date de création de l'équipement dans jeedom
- **Date mise à jour** : Date de mise à jour de l'équipement dans jeedom
- **Nom** : Nom et prénom lié à l'appareil
- **Localisation** : Localisation de l'appareil
- **SSID Wifi** : Nom du Wifi auquel est rattaché l'appareil
- **Aperçu** : Image d l'appareil

* Informations récupérées :
- Ce jour (gallons_used_today) : Cette donnée ....


* Commandes action
- Rafraichir (refresh) : Action permettant de rafraichir les données à la demande