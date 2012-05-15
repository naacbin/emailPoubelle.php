<?php

//-----------------------------------------------------------
// Emeail Poubelle config
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [.] mercereau [aro] zici [.] fr
// Home : http://zici.fr/emailPoubelle.html
//----------------------------------------------------------- 

#error_reporting(0);
error_reporting(E_ALL);

define('VERSION', '0.1.1');

define('DOMAIN', 'zici.fr');

// Serveur DNS pour la résolution/vérification du nom de domaine
define('NS1', 'ns1.fdn.org');

define('BIN_POSTMAP', '/usr/sbin/postmap');

define('URLPAGE', 'http://zici.fr/emailPoubelle.html');

// A indiquer si vous utiliser les URL's rewriting
// Exemple avec un htaccess
// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  index.php?page=emailPoubelle&Validemail=$1  [L]
define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
define('URLREWRITE_FIN', '.html');
// Désactiver
# define('URLREWRITE_DEBUT', false);
# define('URLREWRITE_FIN', false);

define('FICHIERALIAS', './emailPoubelle/postfix/virtual');

// Email de confirmation
define('EMAIL_SUJET', '[zici] [EmailPoubelle] Confirmation alias ');
define('EMAIL_FROM', '"NO REPLAY emailPoubelle" <emailpoubelle@zici.fr>');

?>
