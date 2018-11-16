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

session_start();

$auth=false;
if (isset($_POST['adminPassword'])) {
	$_SESSION['adminPasswordHash'] = password_hash($_POST['adminPassword'], PASSWORD_DEFAULT);
}
if (isset($_SESSION['adminPasswordHash'])) {
	if (password_verify(ADMIN_PASSWORD, $_SESSION['adminPasswordHash'])) {
		$auth=true;
	} else {
		$auth=false;
	}
}
if (isset($_POST['adminPassword']) && $auth==false) {
	echo '<div class="highlight-1">'._('Error: Incorrect password').'</div>';
}
if (empty($_SESSION['adminPasswordHash']) || $auth == false) {
	echo '<form action="#" method="post">
	<label>'.('The admin password').' : </label>
	<input type="password" name="adminPassword" />
	<input type="submit" />
	</form>';
}

// Test connexion, si c'est ok : 
if ($auth==true) {
	languesSwitch();
	// Connect DB
	try {
		if (preg_match('/^sqlite/', DB)) {
			$dbco = new PDO(DB);
		} else {
			$dbco = new PDO(DB, DBUSER, DBPASS);
		}
		$dbco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch ( PDOException $e ) {
		die('DB connect error :  '.$e->getMessage());
	}
	if (isset($_POST['action'])) {
		if ($_POST['action'] == 'DeleteEmail' && isset($_POST['email'])) {
			DeleteEmail($_POST['email']);
		}
		if ($_POST['action'] == 'AddBlacklistEmail' && isset($_POST['email'])) {
			AddBlacklistEmail($_POST['email']);
		}
		if ($_POST['action'] == 'DeleteAlias' && isset($_POST['id']) && isset($_POST['alias'])) {
			 DeleteAlias($_POST['id'], $_POST['alias']);
		}
		if ($_POST['action'] == 'DisableAlias' && isset($_POST['id']) && isset($_POST['alias'])) {
			 DisableAlias($_POST['id'], $_POST['alias'], $_POST['email']);
		}
	}
	?>
	<script>
	function confirmation(idForm, idAction, action) {
		if (confirm(action + " : êtes-vous sûr ?")) {
			document.getElementById(idAction).value = action;
			document.getElementById(idForm).submit();
		} 
	}
    </script>
	<?php
	echo '<p>'._('Statistics').' : </p>';
	echo '<ul>';
	echo '<li>'._('Total alias').' : '.$dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias")->fetchColumn().'</li>';
	echo '<li>'._('Active alias').' : '.$dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE status = 5")->fetchColumn().'</li>';
	echo '<li>'._('Alias suspended').' : '.$dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE status = 3")->fetchColumn().'</li>';
	echo '<li>'._('Alias not verified').' : '.$dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE status = 0")->fetchColumn().'</li>';
	//echo '<li>Email différent : '.$dbco->query("SELECT DISTINCT count(email) FROM ".DBTABLEPREFIX."alias WHERE status = 5")->fetchColumn().'</li>';
	echo '</ul>';
	
	echo '<h3 id="user">User info</h3>';
	echo '<form action="#" method="post">
	<label>'._('User email').' : </label>
	<input type="text" value="'.$_POST['email'].'" name="email" />
	<input type="submit" />
	</form>';
	if (isset($_POST['email'])) {
		$requestUtilisateur = $dbco->query("SELECT * FROM ".DBTABLEPREFIX."alias WHERE email='".$_POST['email']."' ORDER BY dateCreat DESC")->fetchAll() ;
		echo '<p>User '.$_POST['email'].' : ';
		if (count($requestUtilisateur) != 0) {
			echo '<img onclick="confirmation(\'uniqemail_'.$_POST['email'].'\', \'uniqaction_'.$_POST['email'].'\', \'DeleteEmail\')" src="'.URLINC.'/sup.png" alt="sup" />';
			if (!BlacklistEmail($_POST['email'])) {
				echo '<img onclick="confirmation(\'uniqemail_'.$_POST['email'].'\', \'uniqaction_'.$_POST['email'].'\', \'AddBlacklistEmail\')" src="'.URLINC.'/blk.png" alt="blk" />';
			}
		} else {
			echo 'Not found !';
		}
		echo '<form style="display: none" method="post" action="#" id="uniqemail_'.$_POST['email'].'">
		<input type="hidden" name="email" value="'.$_POST['email'].'" />
		<input type="hidden" id="uniqaction_'.$_POST['email'].'" name="action" value="" />
		</form>';
		echo '</p>';
		if (count($requestUtilisateur) != 0) {
			echo '<table>';
			echo '<tr>
					<th>Status</th>
					<th>Alias</th>
					<th>DateCreat</th>
					<th>DateExpir</th>
					<th>Comment</th>
			</tr>';
			foreach ($requestUtilisateur as $utilisateur) {
				echo '<tr>
					<td><img src="'.URLINC.'/status'.$utilisateur['status'].'.png" alt="'.$utilisateur['status'].'" /></td>
					<td>'.$utilisateur['alias'].'
					<br /><form style="display: none" method="post" action="#" id="alias'.$utilisateur['id'].'">
					<input type="hidden" name="id" value="'.$utilisateur['id'].'" />
					<input type="hidden" name="alias" value="'.$utilisateur['alias'].'" />
					<input type="hidden" name="email" value="'.$_POST['email'].'" />
					<input type="hidden" id="action'.$utilisateur['id'].'" name="action" value="" />
					</form>
					<img onclick="confirmation(\'alias'.$utilisateur['id'].'\', \'action'.$utilisateur['id'].'\', \'DeleteAlias\')" src="'.URLINC.'/sup.png" alt="sup" />';
					if ($utilisateur['status'] == 5) {
						echo '<img onclick="confirmation(\'alias'.$utilisateur['id'].'\', \'action'.$utilisateur['id'].'\', \'DisableAlias\')" src="'.URLINC.'/status3.png" alt="Suspendre" />';
					}
					echo '</td>
					<td>'.$utilisateur['dateCreat'].'</td>
					<td>'.$utilisateur['dateExpir'].'</td>
					<td>'.$utilisateur['comment'].'</td>
				</tr>';
			}
			echo '</table>';
		}
	}
	
	echo '<h3 id="top">Top user </h3>';
	$recordActifs = $dbco->query("SELECT email, count(alias) calias FROM ".DBTABLEPREFIX."alias WHERE status=5 GROUP BY email ORDER BY calias DESC LIMIT 40")->fetchAll();
	echo '<table>';
	echo '<tr>
			<th>Email</th>
			<th style="text-align: center">Number of alias</th>
			<th style="text-align: center">Action</th>
	</tr>';
	foreach ($recordActifs as $recordActif) {
		echo '<tr>
			<td>';
			if (BlacklistEmail($recordActif['email'])) {
				echo '<img src="'.URLINC.'/blk.png" alt="blk" /> ';
			}
			echo $recordActif['email'].'</td>
			<td style="text-align: center">'.$recordActif['calias'].'</td>
			<td style="text-align: center">
				<form style="display: none" method="post" action="#" id="email_'.$recordActif['email'].'">
				<input type="hidden" name="email" value="'.$recordActif['email'].'" />
				<input type="hidden" id="action_'.$recordActif['email'].'" name="action" value="" />
				</form>
				<img onclick="confirmation(\'email_'.$recordActif['email'].'\', \'action_'.$recordActif['email'].'\', \'DeleteEmail\')" src="'.URLINC.'/sup.png" alt="sup" />';
				if (!BlacklistEmail($recordActif['email'])) {
					echo '<img onclick="confirmation(\'email_'.$recordActif['email'].'\', \'action_'.$recordActif['email'].'\', \'AddBlacklistEmail\')" src="'.URLINC.'/blk.png" alt="blk" />';
				}
				echo '
			</td>
		</tr>';
	}
	echo '</table>';
	
}



?>
