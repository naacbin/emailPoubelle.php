<?php

//-----------------------------------------------------------
// emailPoubelle config
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [.] mercereau [aro] zici [.] fr
// Home : http://poubelle.zici.fr
//----------------------------------------------------------- 

error_reporting(0);

define('VERSION', '0.3');

// Domaine email
define('DOMAIN', 'zici.fr');

// Serveur DNS pour la résolution/vérification du nom de domaine
define('NS1', 'ns1.fdn.org');
define('NS2', '8.8.8.8');

define('DEBUG', false);

// Fichier d'alias postfix
define('FICHIERALIAS', './emailPoubelle/postfix/virtual');
define('BIN_POSTMAP', '/usr/sbin/postmap');

define('URLPAGE', 'http://www.zici.fr/emailPoubelle.html');

// A indiquer si vous utiliser les URL's rewriting
// Exemple avec un htaccess
// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  index.php?page=emailPoubelle&Validemail=$1  [L]
define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
define('URLREWRITE_FIN', '.html');
// Désactiver
# define('URLREWRITE_DEBUT', false);
# define('URLREWRITE_FIN', false);

// - Email 
// Sujet de l'email pour la confirmation
define('EMAIL_SUJET_CONFIRME', '[zici] [EmailPoubelle] Confirmation alias ');
// Sujet de l'email pour la liste des alias
define('EMAIL_SUJET_LISTE', '[zici] [EmailPoubelle] Liste des alias ');
// From de l'email
define('EMAIL_FROM', '"NO REPLAY emailPoubelle" <emailpoubelle@zici.fr>');

// Alisas interdit : (regex ligne par ligne) - commenter pour désactiver
define('ALIASDENY', './emailPoubelle/aliasdeny.txt');

// Blackliste d'email : (regex ligne par ligne) - commenter pour désactiver
define('BLACKLIST', './emailPoubelle/blacklist.txt');

?>
