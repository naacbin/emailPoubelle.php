<?php

//-----------------------------------------------------------
// Title : Email Poubelle
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Author : David Mercereau - david [aro] mercereau [.] info
// Home : http://poubelle.zici.fr
// Date : 08/2013
// Version : 1.0
// Depend : Postifx (postmap command) php-pdo
//----------------------------------------------------------- 

include_once('./conf.php');
define('VERSION', '1.0');

//////////////////
// Init & check
//////////////////

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
	echo '<div class="highlight-2">Debug activé</div>';
    print_r($_REQUEST);
}

if (defined(DOMAIN)) {
	exit('<div class="highlight-1">Erreur : Problème de configuration</div>');
}
// check writable work directory
if (!is_writable(DATA)) {
	exit('<div class="highlight-1">Erreur : le répertoire de travail ne peut pas être écrit. Merci de contacter l\'administrateur</div>');
}
// check alias file is_writable 
if (!is_writable(FICHIERALIAS)) {
	exit('<div class="highlight-1">Erreur : le fichier d\'alias ne peut pas être écrit. Merci de contacter l\'administrateur</div>');
}
// check blacklist file is_writable
if (defined('BLACKLIST') && !is_readable(BLACKLIST)) {
    exit('<div class="highlight-1">Erreur : un fichier de blacklist est renseigné mais n\'est pas lisible. Merci de contacter l\'administrateur</div>');
}
// check aliasdeny file is_writable
if (defined('ALIASDENY') && !is_readable(ALIASDENY)) {
    exit('<div class="highlight-1">Erreur : un fichier d\'alias interdit est renseigné mais n\'est pas lisible. Merci de contacter l\'administrateur</div>');
}

// Connect DB
try {
	if (preg_match('/^sqlite/', DB)) {
		$dbco = new PDO(DB);
	} else {
		$dbco = new PDO(DB, DBUSER, DBPASS);
	}
	$dbco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch ( PDOException $e ) {
	die('Connexion à la base '.$e->getMessage());
}
// Create DB if not exists
try {
// status : 0=not verified - 3=disable - 5=active
$create = $dbco->query("CREATE TABLE IF NOT EXISTS ".DBTABLEPREFIX."alias (
						id INTEGER PRIMARY KEY  AUTO_INCREMENT,
						status INTEGER(1) NOT NULL,
						alias CHAR(150) NOT NULL UNIQUE,
						email CHAR(150) NOT NULL,
						dateCreat DATETIME NOT NULL,
						dateExpir DATETIME,
						comment TEXT);");
} catch ( PDOException $e ) {
	echo '<div class="highlight-1">Erreur à l\'initialisation des tables. Merci de contacter l\'administrateur ';
	if (DEBUG) { $e->getMessage(); }
	echo '</div>';
	die();
}

//////////////////
// Function 
//////////////////

// Verification des emails
function VerifMXemail($email) {
    if (CHECKMX) {
    	$domaine=explode('@', $email);
    	$r = new Net_DNS2_Resolver(array('nameservers' => array(NS1, NS2)));
    	try {
            $result = $r->query($domaine[1], 'MX');
    	} catch(Net_DNS2_Exception $e) {
    		return false;
    	}
    	if ($result->answer) {
    		return true;
    	} else {
    		return false;
    	}
    } else {
        return true;
    }
}

// postmap command
function UpdateVirtualDB() {
	// @todo : créer le ficheir à partir de la base
	//echo exec(BIN_POSTMAP.' '.FICHIERALIAS,$output,$return);
}

// add new alias
function AjouterAlias($status, $alias,$email, $life, $comment) {
	global $dbco;
	$dateCreat=date('Y-m-d H:i:s');
	if ($life == 0) {
		$dateExpir=NULL;
	} else {
		$dateExpir=date('Y-m-d H:i:s', time()+$life);
	}
	$insertcmd = $dbco->prepare("INSERT INTO ".DBTABLEPREFIX."alias (status, alias, email, dateCreat, dateExpir, comment) 
                					VALUES ($status, '$alias', '$email', '$dateCreat', '$dateExpir', '$comment')");
	$insertcmd->execute();
	if (!$insertcmd) {
		echo '<div class="highlight-1">Erreur pendant l\'ajout dans la base. Merci de contacter l\'administrateur ';
		if (DEBUG) {
			print_r($dbco->errorInfo());
		}
		echo '</div>';
	} else {
		return $dbco->lastInsertId();
	}
	// @todo : a faire
	UpdateVirtualDB();
}

// delete new alias
function SupprimerAlias($alias,$email) {
	// @todo : a faire
	UpdateVirtualDB();
}

// update alias status
function UpdateStatusAlias($id, $alias_full, $status) {
	global $dbco;
	try {
		$updatecmd = $dbco->prepare("UPDATE ".DBTABLEPREFIX."alias SET status = $status WHERE id = $id AND alias = '$alias_full'");
		$updatecmd->execute();
	} catch ( PDOException $e ) {
		echo "DB error :  ", $e->getMessage();
		die();
	}
	UpdateVirtualDB();
}

// parse file for blacklist and aliasdeny
function parseFileRegex($file, $chaine) {
    $return=false;
    $handle = fopen($file, 'r');
    while (!feof($handle)) {
        $buffer = fgets($handle);
        $buffer = str_replace("\n", "", $buffer);
        if ($buffer) {
            if (!preg_match('/^(#|$|;)/', $buffer) && preg_match($buffer, $chaine)) {
                $return=true;
                break;
            }
        }
    }
    fclose($handle);
    return $return;
}

// Check blacklistemail
function BlacklistEmail($email) {
    if (defined('BLACKLIST')) {
        return parseFileRegex(BLACKLIST, $email);
    } else {
        return false;
    }
}

// check aliasdeny
function AliasDeny($alias) {
    if (defined('ALIASDENY')) {
        return parseFileRegex(ALIASDENY, $alias);
    } else {
        return false;
    }
}

//// A FAIRE :

// list alias 
function ListeAlias($email) {
	// @todo : a faire
    return ($alias);
}

function SendEmail($recipient, $sujet, $message) {
	$header = 'From: '.EMAILFROM.'\n';
	$header.= 'MIME-Version: 1.0\n';
	if (preg_match('#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#', $recipient)) {
		$header = str_replace('\n', '\r\n', $header);
		$message = str_replace('\n', '\r\n', $header);
	}
	mail($recipient,EMAILTAGSUJET.' '.$sujet,$message,$header);
}

function urlGen($act,$id,$alias_full) {
	$idUrl=base64_encode($id.';'.$alias_full);
	if (URLREWRITE_DEBUT && URLREWRITE_FIN) {
		return URLREWRITE_DEBUT.$idUrl.URLREWRITE_FIN;
	} else {
		return URLPAGE."?act=".$act."&value=".$idUrl;
	}
}

//////////////////
// Admin function
//////////////////

function CheckUpdate() {
	//$doc = file_get_contents('http://poubelle.zici.fr/ep_checkupdate');
	//echo $doc;
}

//////////////////
// Start program
//////////////////

// Valid email process
if (isset($_GET['act']) && $_GET['act'] == 'validemail' && isset($_GET['value'])) {
	$idUrl = explode(';', base64_decode($_GET['value']));
	echo $dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE id = '".$idUrl[0]."' AND status = 0")->fetchColumn();
	if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE id = '".$idUrl[0]."' AND status = 0")->fetchColumn() != 0) {
		UpdateStatusAlias($idUrl[0], $idUrl[1], 5);
		echo '<div class="highlight-3">Votre email poubelle <b>'.$idUrl[1].'</b> est maintenant actif</div>';
	} else {
		echo '<div class="highlight-1">Erreur : ID introuvable ou déjà validé</div>';
	}
} elseif (isset($_GET['dis'])) {
	// @todo fa faire
} elseif (isset($_GET['del'])) {
	// @todo fa faire
// list email process
} elseif (isset($_GET['list'])) {
    $email=strtolower($_REQUEST['email']);
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
    } else if (!preg_match('#\n[a-z0-9]+@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
        echo '<div class="highlight-1">Vous n\'avez encore aucun alias d\'actif</div>';
     } else {
        $header = 'From: '.EMAIL_FROM.'\n';
        $header.= 'MIME-Version: 1.0\n';
        $message= 'Liste de vos redirections poubelles : \n';
        foreach (ListeAlias($email) as $alias) {
            $message.=' * '.$alias.'\n';
        }
        $message.= 'Pour supprimer un email poubelle vous pouvez vous rendre sur le lien ci-dessou : \n';
        $message.= "\t * ".URLPAGE.'\n';
        SendEmail($email,'Liste des alias',$message);
        echo '<div class="highlight-3">Un email vous a été adressé avec la liste de vos emails poubelles actifs.</div>';
    }
// Form
} elseif (isset($_POST['email']) && isset($_POST['alias'])) {
	$alias=strtolower($_POST['alias']);
	$email=strtolower($_POST['email']);
	$domain=$_POST['domain'];
	$life=$_POST['life'];
	$comment=$_POST['comment'];
	// Check form
	if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
	} else if (! preg_match('#^[\w.-]+$#',$alias)) {
		echo '<div class="highlight-1">Erreur : Format de l\'email poubelle incorrect</div>';
	} else if (! preg_match('#'.$domain.'#',DOMAIN)) {
		echo '<div class="highlight-1">Erreur : ce domain n\'est pas pris en charge</div>';
	} else if (AliasDeny($alias)) {
		echo '<div class="highlight-1">Erreur : email poubelle interdit</div>';
    } else if (BlacklistEmail($email)) {
        echo '<div class="highlight-1">Erreur : vous avez été blacklisté sur ce service</div>';
	// add 
	} elseif (isset($_POST['add'])) {
        if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE alias = '".$alias.'@'.$domain."'")->fetchColumn() != 0) {
			echo '<div class="highlight-1">Erreur : cet email poubelle est déjà utilisé</div>';
		} else {
			if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE email = '".$email."' AND status = 5")->fetchColumn() != 0) {
				AjouterAlias(5, $alias.'@'.$domain, $email, $life, $comment);
				echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.$domain.' > '.$email.'</b> est maintenant actif</div>';
			} else {
				$lastId=AjouterAlias(0, $alias.'@'.$domain, $email, $life, $comment);
				$message= "Confirmation de la création de votre redirection email poubelle : ";
				$message= $alias.'@'.$domain.' => '.$email."\n";
				$message= "Cliquer sur le lien ci-dessous pour confirmer : \n";
				$message.= "\t * ".urlGen('validemail',$lastId,$alias.'@'.$domain)."\n";
				$message.= "Pour suspendre temporairement cet email poubelle vous pouvez vous rendre sur le lien ci-dessou : \n";
				$message.= "\t * ".urlGen('dis',$lastId,$alias.'@'.$domain)."\n";
				$message.= "Pour supprimer cet email poubelle vous pouvez vous rendre sur le lien ci-dessou : \n";
				$message.= "\t * ".urlGen('del',$lastId,$alias.'@'.$domain)."\n";
				SendEmail($email,'Confirmation alias '.$alias,$message);
				echo '<div class="highlight-2">Votre email ('.$email.') nous étant inconnu, une confirmation vous a été envoyé par email.</div>';
			}
		}
	// delete
	} else if (isset($_POST['del'])) {
		if ($id = $dbco->query("SELECT id FROM ".DBTABLEPREFIX."alias WHERE email = '".$email."' AND alias = '".$alias.'@'.$domain."'")->fetchColumn()) {
			$message= "Confirmation de la création de votre redirection email poubelle : ";
			$message= $alias.'@'.$domain.' => '.$email."\n";
			$message= "Cliquer sur le lien ci-dessous pour confirmer la suppression : \n";
			$message.= "\t * ".urlGen('del',$id,$alias.'@'.$domain)."\n\n";
			$message.= "Sinon pour suspendre temporairement cet email poubelle vous pouvez vous rendre sur le lien ci-dessou : \n";
			$message.= "\t * ".urlGen('dis',$id,$alias.'@'.$domain)."\n";
			SendEmail($email,'Suppression de l\'alias '.$alias,$message);
			echo '<div class="highlight-2">Un email de confirmation vient de vous être envoyé.</div>';
		} else {
			echo '<div class="highlight-1">Erreur : impossible de trouver cet email poubelle</div>';
		}
	// disable
	} else if (isset($_POST['dis'])) {
		// @todo a faire
		if ($return=DisableAlias($alias.'@'.$domain,$email)) {
			echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.$domain.'</b> est maintenant suspendu !</div>';
		} else {
			echo '<div class="highlight-1">Erreur : '.$return.'</div>';
		}
	}
	if (isset($_POST['memory'])) {
		setcookie ("email", $email, time() + 31536000);
	} else if (isset($_COOKIE['email'])) {
		unset($_COOKIE['email']);
	}
}
// Close connexion DB
$dbco = null;

//////////////////
// Printing form
//////////////////

?>

<form action="<?= URLPAGE?>" method="post">
<div id="form-email">
	<label for="email">Votre email réel : </label>
	<input type="text" name="email" <?php if (isset($_COOKIE['email'])) { echo 'value="'.$_COOKIE['email'].'"'; } ?> id="input-email" size="24" border="0"  onkeyup="printForm()" onchange="printForm()"  /> 
	<input class="button2" type="submit" name="list" id="button-list" value="Lister" />
	<input type="checkbox" name="memory" id="check-memory" <?php if (isset($_COOKIE['email'])) { echo 'checked="checked" '; } ?>/> Mémoriser
</div>
<div id="form-alias">
	<label for="alias">Nom de l'email poubelle : </label>
	<input type="text" name="alias" id="input-alias" size="24" border="0" onkeyup="printForm()" onchange="printForm()" placeholder="Ex : jean-petiteannonce" /> @<?php
		$domains = explode(';', DOMAIN);
		if (count($domains) == 1) {
			echo DOMAIN.'<input type="hidden" value="'.DOMAIN.'" name="domain" id="input-domain" />';
		} else {
			echo '<select name="domain" id="input-domain">';
			foreach ($domains as $one_domain)  {
				echo '<option value="'.$one_domain.'">'.$one_domain.'</option>';
			}
			echo '</select>';
		}
	?>
	<select name="life" id="input-life">
		<option value="0">Illimité</option>
		<option value="7200">2 heure</option>
		<option value="21600">6 heures</option>
		<option value="86400">1 jour</option>
		<option value="604800">7 jours</option>
		<option value="1296000">15 jours</option>
		<option value="2592000">30 jours</option>
		<option value="7776000">90 jours</option>
	</select>
</div>
<div id="form-comment">
	<label for="comment">Un commentaire pour l'ajout ? (pour votre mémoire)</label>
	<input type="text" name="comment" size="54" placeholder="Ex : Inscription sur zici.fr" />
</div>
<div id="form-submit">
	<input class="button" type="submit" id="button-add" name="add" value="Activer" /> -
	<input class="button" type="submit" id="button-dis" name="dis" value="Susprendre" /> -
	<input class="button" type="submit" id="button-del" name="del" value="Supprimer" />
</div>
</form>
<script type="text/javascript">
	function validateEmail(email) { 
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(email);
	} 
	function printForm() {
		console.log("appel de la fonction : " + document.getElementById('input-email').value + document.getElementById('input-alias').value);
		if (validateEmail(document.getElementById('input-email').value) && document.getElementById('input-alias').value != '') {
			console.log("Les 2 sont OK");
			document.getElementById('input-alias').disabled = false; 
			document.getElementById('input-domain').disabled = false; 
			document.getElementById('button-list').disabled = false; 
			document.getElementById('button-add').disabled = false; 
			document.getElementById('button-dis').disabled = false; 
			document.getElementById('button-del').disabled = false; 
			document.getElementById('input-life').disabled = false; 
			document.getElementById('form-comment').style.display = "block" ;
		} else if (validateEmail(document.getElementById('input-email').value)) {
			console.log("email ok");
			document.getElementById('input-alias').disabled = false; 
			document.getElementById('input-domain').disabled = false; 
			document.getElementById('button-list').disabled = false;
			document.getElementById('input-life').disabled = false;
			document.getElementById('form-comment').style.display = "display" ;
			document.getElementById('button-add').disabled = true; 
			document.getElementById('button-dis').disabled = true; 
			document.getElementById('button-del').disabled = true; 
			document.getElementById('input-life').disabled = true;
			document.getElementById('form-comment').style.display = "none" ;
		} else {
			console.log("rien OK");
			document.getElementById('input-alias').disabled = true; 
			document.getElementById('input-domain').disabled = true; 
			document.getElementById('button-list').disabled = true; 
			document.getElementById('button-add').disabled = true; 
			document.getElementById('button-dis').disabled = true; 
			document.getElementById('button-del').disabled = true; 
			document.getElementById('input-life').disabled = true;
			document.getElementById('form-comment').style.display = "none" ;
		}
	}
	printForm();
</script>
<p>Version <?= VERSION ?> - Créé par David Mercereau sous licence GNU GPL v3</p>
<p>Télécharger et utiliser ce script sur le site du projet <a target="_blank" href="http://forge.zici.fr/p/emailpoubelle-php/">emailPoubelle.php</a></p>
