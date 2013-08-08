<?php

//-----------------------------------------------------------
// Email Poubelle
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [.] mercereau [aro] zici [.] fr
// Home : http://poubelle.zici.fr
// Date : 08/2013
// Version : 0.3
// Dépendance : Postifx
//----------------------------------------------------------- 

include_once('./conf.php');

# Init & vérif
if (!is_writable(FICHIERALIAS)) {
	exit('<div class="highlight-1">Erreur : le fichier d\'alias ne peut pas être écrit. Merci de contacter l\'administrateur</div>');
}
if (defined('BLACKLIST') && !is_readable(BLACKLIST)) {
    exit('<div class="highlight-1">Erreur : un fichier de blacklist est renseigné mais n\'est pas lisible. Merci de contacter l\'administrateur</div>');
}
if (defined('ALIASDENY') && !is_readable(ALIASDENY)) {
    exit('<div class="highlight-1">Erreur : un fichier d\'alias interdit est renseigné mais n\'est pas lisible. Merci de contacter l\'administrateur</div>');
}

function VerifMXemail($email) {
	require_once 'Net/DNS2.php';
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
}

function UpdateVirtualDB() {
	echo exec(BIN_POSTMAP.' '.FICHIERALIAS,$output,$return);
}
function AjouterAlias($alias,$email) {
	$fichier=fopen(FICHIERALIAS,'a+'); 
	fputs($fichier, $alias.'@'.DOMAIN.' '.$email."\n");
	fclose($fichier);
	UpdateVirtualDB();
}
function SupprimerAlias($alias,$email) {
	file_put_contents(FICHIERALIAS, preg_replace('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	file_put_contents(FICHIERALIAS, preg_replace('#\n'.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	# http://www.siteduzero.com/forum-83-542138-p1-supprimer-une-ligne-d-un-fichier-texte-avec-regex.html
	UpdateVirtualDB();
}

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

function BlacklistEmail($email) {
    if (defined('BLACKLIST')) {
        return parseFileRegex(BLACKLIST, $email);
    } else {
        return false;
    }
}

function AliasDeny($alias) {
    if (defined('ALIASDENY')) {
        return parseFileRegex(ALIASDENY, $alias);
    } else {
        return false;
    }
}

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

if (DEBUG) {
	echo '<div class="highlight-2">Debug activé</div>';
    print_r($_REQUEST);
}

echo '<h1>Emails poubelle libre</h1>
<p>Générer des emails poubelle sans contrainte de durée de vie. </p>';

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
} elseif (isset($_REQUEST['list'])) {
    $email=strtolower($_REQUEST['email']);
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
    } else if (!preg_match('#\n[a-z0-9]+@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
        echo '<div class="highlight-1">Vous n\'avez encore aucun alias d\'actif</div>';
     } else {
        # Envoi de l'email récapitulatif : 
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
	} elseif (isset($_REQUEST['ajo'])) {
        if (preg_match('#\n'.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS)) || preg_match('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS))) {
			echo '<div class="highlight-1">Erreur : cet email poubelle est déjà utilisé</div>';
		} else {
			if (preg_match('#\n[a-z0-9]+@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
				AjouterAlias($alias,$email);
				echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.' > '.$email.'</b> est maintenant actif</div>';
			} else {
				$id=rand().date('U');
				$alias_desactive='#'.$id.' '.$alias;
				AjouterAlias($alias_desactive,$email);
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
	} else if (isset($_REQUEST['sup'])) {
		if (preg_match('#\n'.$alias.'@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS)) || preg_match('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.' '.$email.'#', file_get_contents(FICHIERALIAS))) {
			SupprimerAlias($alias,$email);
			echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.'</b> est maintenant supprimé !</div>';
		} else {
			echo '<div class="highlight-1">Erreur : l\'email poubelle n\'existe pas</div>';
		}
	}
}
?>
<form action="<?= URLPAGE?>" method="post">
<label>Nom de l'email poubelle : </label>
<input type="text" name="alias" size="24" border="0" /> @<?= DOMAIN; ?>	
<br />
<label>Votre email réel : </label>
<input type="text" name="email" size="24" border="0" /> <input class="button" type="submit" name="list" value="Lister" />
<br />
<input class="button" type="submit" name="ajo" value="Créer" /> ou
<input class="button" type="submit" name="sup" value="Supprimer" /> la redirection poubelle
</form>
<p>Version <?= VERSION ?> - Créé par David Mercereau sous licence GNU GPL v3</p>
<p>Télécharger et utiliser ce script sur le site du projet <a target="_blank" href="http://forge.zici.fr/p/emailpoubelle-php/">emailPoubelle.php</a></p>
