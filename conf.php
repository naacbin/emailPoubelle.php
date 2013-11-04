<?php

//-----------------------------------------------------------
// emailPoubelle config
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [aro] mercereau [.] info
// Home : http://poubelle.zici.fr
//----------------------------------------------------------- 

//error_reporting(0);
define('DEBUG', true);




// Domaine email
define('DOMAIN', 'zici.fr');

// Deux options : 
// 		PLAIN : plaintext, pas de base, simple mais des fonctionnalités en moins
//		DB : pdo usage
define('BACKEND', 'DB');

if (BACKEND == 'DB') {
    // PDO stucture
    // Exemple pour MYSQL : 
    //      define('DB', 'mysql:host=127.0.0.1;dbname=baseMysql');
    //      define('DBUSER', 'utilisateurMysql');
    //      define('DBPASS', 'motdepassedefou');
    // Exemple pour Sqlite : 
    //      define('DB', 'sqlite:./data/emailPoubelle.sqlite');
    define('DB', 'mysql:host=localhost;dbname=c1_demo');
    #define('DB', 'sqlite:./database.sdb');
    define('DBUSER', 'c1_demo');
    define('DBPASS', 'sqdf2csd4rvn45548');
}

// Fichier d'alias postfix
define('FICHIERALIAS', './data/virtual');
define('BIN_POSTMAP', '/usr/sbin/postmap');

define('URLPAGE', 'http://'.$_SERVER["SERVER_NAME"].'/'.$_SERVER["REQUEST_URI"]);

// A indiquer si vous utiliser les URL's rewriting
// Exemple avec un htaccess
// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  folder/emailPoubelle.php?&Validemail=$1  [L]
//define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
//define('URLREWRITE_FIN', '.html');
// Désactiver
define('URLREWRITE_DEBUT', false);
define('URLREWRITE_FIN', false);

// - Email 
// Sujet de l'email pour la confirmation
define('EMAIL_SUJET_CONFIRME', '[EmailPoubelle] Confirmation alias ');
// Sujet de l'email pour la liste des alias
define('EMAIL_SUJET_LISTE', '[EmailPoubelle] Liste des alias ');
// From de l'email
define('EMAIL_FROM', '"NO REPLAY emailPoubelle" <emailpoubelle@exemple.com>');

// Alisas interdit : (regex ligne par ligne) - commenter pour désactiver
define('ALIASDENY', './aliasdeny.txt');

// Blackliste d'email : (regex ligne par ligne) - commenter pour désactiver
define('BLACKLIST', './blacklist.txt');

// Depend pear Net/DNS2
define('CHECKMX', false);
if (CHECKMX) {
    require_once('Net/DNS2.php');
    // Serveur DNS pour la résolution/vérification du nom de domaine
    define('NS1', 'ns1.fdn.org');
    define('NS2', '8.8.8.8');
}

?>
