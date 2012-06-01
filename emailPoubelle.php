<?php

//-----------------------------------------------------------
// Emeail Poubelle
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Créateur : David Mercereau - david [.] mercereau [aro] zici [.] fr
// Home : http://poubelle.zici.fr
// Date : 02/2012
// Version : 0.1
// Dépendance : Postifx
//----------------------------------------------------------- 

include_once('./conf.php');

function VerifMXemail($email)
{
	require_once 'Net/DNS.php';
	$domaine=explode('@', $email);
	$serveurDeNom=array(
	   NS1
	);
	$resolver = new Net_DNS_Resolver();
	$resolver->nameservers=$serveurDeNom;
	$response = $resolver->query($domaine[1],'MX');
	if ($response) {
		return true;
	} else {
		return false;
	}
}
function UpdateVirtualDB()
{
	echo exec(BIN_POSTMAP.' '.FICHIERALIAS,$output,$return);
}
function AjouterAlias($alias,$email)
{
	$fichier=fopen(FICHIERALIAS,'a+'); 
	fputs($fichier, $alias.'@'.DOMAIN.' '.$email."\n");
	fclose($fichier);
	UpdateVirtualDB();
}

function SupprimerAlias($alias,$email)
{
	file_put_contents(FICHIERALIAS, preg_replace('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	file_put_contents(FICHIERALIAS, preg_replace('#\n'.$alias.'@'.DOMAIN.' '.$email.'#U', '', file_get_contents(FICHIERALIAS)/*, 1*/));
	# http://www.siteduzero.com/forum-83-542138-p1-supprimer-une-ligne-d-un-fichier-texte-avec-regex.html
	UpdateVirtualDB();
}

echo '<h1>Emails poubelle libre</h1>
<p>Générer des emails poubelle sans contrainte de durée de vie. </p>';

if (isset($_REQUEST['Validemail'])) {
	if (preg_match('/#'.$_REQUEST['Validemail'].' [a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+ [a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+/', file_get_contents(FICHIERALIAS),$res)) {
		$data=explode(' ', $res[0]);
		$aliasZici=explode('@', $data[1]);
		$alias=$aliasZici[0];
		$email=$data[2];
		SupprimerAlias($alias,$email);
		AjouterAlias($alias,$email);
		echo '<div class="highlight-3">Votre email poubelle <b>'.$alias.'@'.DOMAIN.' > '.$email.'</b> est maintenant actif</div>';
	} else {
		echo '<div class="highlight-1">Erreur : ID introuvable</div>';
	}
} else if (isset($_REQUEST['email']) && isset($_REQUEST['alias'])) {
	$alias=strtolower($_REQUEST['alias']);
	$email=strtolower($_REQUEST['email']);
	if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">Erreur : Adresse email incorrect (2)</div>';
	} else if (! preg_match('#^[\w.-]+$#',$alias)) {
		echo '<div class="highlight-1">Erreur : email poubelle incorrect</div>';
	} elseif (isset($_REQUEST['ajo'])) {
		if (preg_match('#\n'.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS)) || preg_match('#\n\#[0-9]+ '.$alias.'@'.DOMAIN.'#', file_get_contents(FICHIERALIAS))) {
			echo '<div class="highlight-1">Erreur : cet email poubelle est déjà utilisé</div>';
		} else {
			if (preg_match('#@'.DOMAIN.' '.$email.'$#', file_get_contents(FICHIERALIAS))) {
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
				mail($email,EMAIL_SUJET.$alias,$message,$header);
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
<input type="text" name="email" size="24" border="0" />
<br />
<input class="button" type="submit" name="ajo" value="Créer" /> ou 
<input class="button" type="submit" name="sup" value="Supprimer" /> la redirection poubelle
</form>
<p>Version <?= VERSION ?> - Créé par David Mercereau sous licence GNU GPL v3</p>
<p>Télécharger et utiliser ce script sur le site du projet <a target="_blank" href="http://forge.zici.fr/projects/emailpoubelle">emailPoubelle.php</a></p>
