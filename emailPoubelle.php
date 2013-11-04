<?php

//-----------------------------------------------------------
// Email Poubelle
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [aro] mercereau [.] info
// Home : http://poubelle.zici.fr
// Date : 08/2013
// Version : 0.4
// Dépendance : Postifx
//----------------------------------------------------------- 

include_once('./conf.php');
define('VERSION', '0.4');

//////////////////
// Init & check
//////////////////

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
	echo '<div class="highlight-2">Debug activé</div>';
    print_r($_REQUEST);
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
if (BACKEND == 'DB') {
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
	echo exec(BIN_POSTMAP.' '.FICHIERALIAS,$output,$return);
}

//// A FAIRE (chian) :

// add new alias
function AjouterAlias($alias,$email) {
    if (BACKEND == 'DB') {
        // A faire mais $alais doît changer
    } else {
	    $fichier=fopen(FICHIERALIAS,'a+'); 
	    fputs($fichier, $alias.'@'.DOMAIN.' '.$email."\n");
	    fclose($fichier);
    }
	UpdateVirtualDB();
}

//// A FAIRE :

// delete new alias
function SupprimerAlias($alias,$email) {
	file_put_contents(FICHIERALIAS, preg_replace('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	file_put_contents(FICHIERALIAS, preg_replace('#\n'.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	# http://www.siteduzero.com/forum-83-542138-p1-supprimer-une-ligne-d-un-fichier-texte-avec-regex.html
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
    $handle = fopen(FICHIERALIAS, 'r');
    while (!feof($handle)) {
        $buffer = fgets($handle);
        if ($buffer) {
            $bufferExplode = explode(' ', $buffer);
            if (!preg_match('/^(#|$|;)/', $buffer) && preg_match('/^'.$email.'$/', $bufferExplode[1])) {
                $alias[]=$bufferExplode[0];
            }
        }
    }
    fclose($handle);
    return ($alias);
}

//////////////////
// Start program
//////////////////

// Valid email process
if (isset($_REQUEST['Validemail'])) {
	if (preg_match('/#'.$_REQUEST['Validemail'].' [a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+ [a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+/', file_get_contents(FICHIERALIAS),$res)) {
		$data=explode(' ', $res[0]);
		$aliasExplode=explode('@', $data[1]);
		$alias=$aliasExplode[0];
		$email=$data[2];
		SupprimerAlias($alias,$email);
		AjouterAlias($alias,$email);
		echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.' > '.$email.'</b> est maintenant actif</div>';
	} else {
		echo '<div class="highlight-1">Erreur : ID introuvable</div>';
	}
// list email process
} elseif (isset($_REQUEST['list'])) {
    $email=strtolower($_REQUEST['email']);
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
    } else if (!preg_match('#\n[a-z0-9]+@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
        echo '<div class="highlight-1">Vous n\'avez encore aucun alias d\'actif</div>';
     } else {
        # send email with alias list 
        if (!preg_match('#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#', $email)) {
            $passage_ligne = "\r\n";
        } else {
            $passage_ligne = "\n";
        }
        $header = 'From: '.EMAIL_FROM.$passage_ligne;
        $header.= 'MIME-Version: 1.0'.$passage_ligne;
        $message= 'Liste de vos redirections poubelles : '.$passage_ligne;
        foreach (ListeAlias($email) as $alias) {
            $message.=' * '.$alias.$passage_ligne;
        }
        $message.= 'Pour supprimer un email poubelle vous pouvez vous rendre sur le lien ci-dessou : '.$passage_ligne;
        $message.= "\t * ".URLPAGE.$passage_ligne;
        mail($email,EMAIL_SUJET_LISTE,$message,$header);
        echo '<div class="highlight-3">Un email vous a été adressé avec la liste de vos emails poubelles actifs.</div>';
    }
// 
} elseif (isset($_REQUEST['email']) && isset($_REQUEST['alias'])) {
	$alias=strtolower($_REQUEST['alias']);
	$email=strtolower($_REQUEST['email']);
	if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
	} else if (! preg_match('#^[\w.-]+$#',$alias)) {
		echo '<div class="highlight-1">Erreur : email poubelle incorrect</div>';
	} else if (AliasDeny($alias)) {
		echo '<div class="highlight-1">Erreur : email poubelle interdit</div>';
    } else if (BlacklistEmail($email)) {
        echo '<div class="highlight-1">Erreur : vous avez été blacklisté sur ce service</div>';
	} elseif (isset($_REQUEST['add'])) {
        if (preg_match('#\n'.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS)) || preg_match('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS))) {
			echo '<div class="highlight-1">Erreur : cet email poubelle est déjà utilisé</div>';
		} else {
			if (preg_match('#\n[a-z0-9]+@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
				AjouterAlias($alias,$email);
				echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.' > '.$email.'</b> est maintenant actif</div>';
			} else {
				$id=rand().date('U');
				$alias_desactive='#'.$id.' '.$alias;
				AjouterAlias($alias_desactive,$email,0);
				# Envoi de l'email : 
				if (!preg_match('#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#', $email)) {
					$passage_ligne = "\r\n";
				} else {
					$passage_ligne = "\n";
				}
				$header = 'From: '.EMAIL_FROM.$passage_ligne;
				$header.= 'MIME-Version: 1.0'.$passage_ligne;
				$message= 'Confirmation de la création de votre redirection email poubelle : '.$passage_ligne;
				$message= $alias.'@'.DOMAIN.' => '.$email.$passage_ligne;
				$message= 'Cliquer sur le lien ci-dessous pour confirmer : '.$passage_ligne;
				if (URLREWRITE_DEBUT && URLREWRITE_FIN) {
					$message.= "\t * ".URLREWRITE_DEBUT.$id.URLREWRITE_FIN.$passage_ligne;
				} else {
					$message.= "\t * ".URLPAGE.'?Validemail='.$id.$passage_ligne;
				}
				$message.= 'Pour supprimer cet email poubelle vous pouvez vous rendre sur le lien ci-dessou : '.$passage_ligne;
				if (URLREWRITE_DEBUT && URLREWRITE_FIN) {
					$message.= "\t * ".URLPAGE.$passage_ligne;
				} else {
					$message.= "\t * ".URLPAGE.'?sup=true&email='.$email.'&alias='.$alias.$passage_ligne;
				}
				mail($email,EMAIL_SUJET_CONFIRME.$alias,$message,$header);
				echo '<div class="highlight-2">Votre email ('.$email.') nous étant inconnu, une confirmation vous a été envoyé par email.</div>';
			}
		}
	} else if (isset($_REQUEST['del'])) {
		if (preg_match('#\n'.$alias.'@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS)) || preg_match('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
			SupprimerAlias($alias,$email);
			echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.'</b> est maintenant supprimé !</div>';
		} else {
			echo '<div class="highlight-1">Erreur : l\'email poubelle n\'existe pas</div>';
		}
	}
}
// Close connexion DB
if (BACKEND == DB) {
    $dbco = null;
}
//////////////////
// Printing form
//////////////////
?>
<form action="<?= URLPAGE?>" method="post">
<label>Nom de l'email poubelle : </label>
<input type="text" name="alias" size="24" border="0" /> @<?= DOMAIN; ?>	
<br />
<label>Votre email réel : </label>
<input type="text" name="email" size="24" border="0" /> <input class="" type="submit" name="list" value="Lister" />
<br />
<input class="button" type="submit" name="add" value="Créer" /> ou
<input class="button" type="submit" name="del" value="Supprimer" /> la redirection poubelle
</form>
<p>Version <?= VERSION ?> - Créé par David Mercereau sous licence GNU GPL v3</p>
<p>Télécharger et utiliser ce script sur le site du projet <a target="_blank" href="http://forge.zici.fr/p/emailpoubelle-php/">emailPoubelle.php</a></p>
