<?php

//-----------------------------------------------------------
// Emeail Poubelle config
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [.] mercereau [aro] zici [.] fr
// Home : http://zici.fr/emailPoubelle.html
//----------------------------------------------------------- 

define('VERSION', '0.2');

define('DOMAIN', 'zici.fr');

// Serveur DNS pour la résolution/vérification du nom de domaine
define('NS1', 'ns1.fdn.org');
define('NS2', '8.8.8.8');

if (basename($_SERVER['SCRIPT_FILENAME']) == 'emailPoubelle_dev.php') {
	# Dev
	define('DEBUG', true);
	error_reporting(E_ALL);
	
	define('FICHIERALIAS', './postfix/virtual_debug');
	
	define('URLPAGE', 'http://www.zici.fr/emailPoubelle/emailPoubelle_dev.php');
	
	// A indiquer si vous utiliser les URL's rewriting
	// Exemple avec un htaccess
	// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  index.php?page=emailPoubelle&Validemail=$1  [L]
	#define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
	#define('URLREWRITE_FIN', '.html');
	// Désactiver
	define('URLREWRITE_DEBUT', false);
	define('URLREWRITE_FIN', false);
} else {
	# Prod
	error_reporting(0);
	define('DEBUG', false);
	
	define('FICHIERALIAS', './emailPoubelle/postfix/virtual');
	
	define('URLPAGE', 'http://www.zici.fr/emailPoubelle.html');
	
	// A indiquer si vous utiliser les URL's rewriting
	// Exemple avec un htaccess
	// 		RewriteRule ^EmailPoubell-([0-9]+)\.html$  index.php?page=emailPoubelle&Validemail=$1  [L]
	define('URLREWRITE_DEBUT', 'http://www.zici.fr/EmailPoubell-');
	define('URLREWRITE_FIN', '.html');
	// Désactiver
	# define('URLREWRITE_DEBUT', false);
	# define('URLREWRITE_FIN', false);
}

define('BIN_POSTMAP', '/usr/sbin/postmap');

// Email de confirmation
define('EMAIL_SUJET', '[zici] [EmailPoubelle] Confirmation alias ');
define('EMAIL_FROM', '"NO REPLAY emailPoubelle" <emailpoubelle@zici.fr>');

#  Alisas interdit :
$aliasInterditListe = array('root', 'mail', 'email', 'test', 'toto', 'www-data', 'www-owne', 'manager', 'admin', 'postmaster', 'MAILER-DAEMON', 'abuse', 'spam', 'backup', 'list', 'nobody', 'vmail', 'mysql', 'web', 'git');

?>
