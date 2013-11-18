<?php

//-----------------------------------------------------------
// emailPoubelle config
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [aro] mercereau [.] info
// Home : http://poubelle.zici.fr
//----------------------------------------------------------- 

// writable for script
define('DATA', '../var');
// include directory
define('INC', '../lib');

// include function
include_once(INC.'/ep_function.php');
include_once(INC.'/ep_admin.php');

define('DEBUG', true);

// Domain email (separe with ;)
define('DOMAIN', 'exemple.com;zici.fr');
#define('DOMAIN', 'exemple.com');

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
define('DBTABLEPREFIX', 'ep_');



// Fichier d'alias postfix
define('FICHIERALIAS', DATA.'/virtual');
define('BIN_POSTMAP', '/usr/sbin/postmap');

define('URLPAGE', 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]);

// A indiquer si vous utiliser les URL's rewriting
// Exemple avec un htaccess
// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  folder/emailPoubelle.php?&Validemail=$1  [L]
//define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
//define('URLREWRITE_FIN', '.html');
// Désactiver
define('URLREWRITE_DEBUT', false);
define('URLREWRITE_FIN', false);

// - Email 
define('EMAILTAGSUJET', '[EmailPoubelle]');
// From de l'email
define('EMAILFROM', '"NO REPLAY emailPoubelle" <emailpoubelle@exemple.com>');
define('EMAILEND', 'emailPoubelle.zici.fr');

// Alisas interdit : (regex ligne par ligne) - commenter pour désactiver
define('ALIASDENY', DATA.'/aliasdeny.txt');

// Blackliste d'email : (regex ligne par ligne) - commenter pour désactiver
define('BLACKLIST', DATA.'/blacklist.txt');

// Depend pear Net/DNS2
define('CHECKMX', false);
if (CHECKMX) {
    require_once('Net/DNS2.php');
    // Serveur DNS pour la résolution/vérification du nom de domaine
    define('NS1', 'ns1.fdn.org');
    define('NS2', '8.8.8.8');
}

// check update :
// 		enable : in seconds
//		disable : false
define('CHECKUPDATE', 300);



?>
