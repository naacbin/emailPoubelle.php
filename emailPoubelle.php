<?php

//-----------------------------------------------------------
// Title : Email Poubelle
// Licence : GNU GPL v3 : http://www.gnu.org/licenses/gpl.html
// Author : David Mercereau - david [aro] mercereau [.] info
// Home : http://poubelle.zici.fr
// Date : 08/2018
// Version : 2.0
// Depend : Postifx (postmap command) php-pdo, http serveur
//----------------------------------------------------------- 

//////////////////
// Init & check
//////////////////

define('VERSION', '2.0');

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
	echo '<div class="highlight-2">'._("Debug enabled") .'<br />';
	echo print_r($_REQUEST);
	echo '</div>';
}

if (!defined('DOMAIN') || !defined('DATA') || !defined('DEBUG') || !defined('FICHIERALIAS') || !defined('DB')) {
	echo '<div class="highlight-1">'._("Error : the configuration file conf.php might not be included because the constants are not declared").'.</div>';
// check writable work directory
} else if (!is_writable(DATA)) {
	echo '<div class="highlight-1">'._("Error : the working directory cannot be written. Please contact the admin").'</div>';
// check alias file is_writable 
} else if (!is_writable(FICHIERALIAS)) {
	echo '<div class="highlight-1">'._("Error : the alias file cannot be written. Please contact the admin").'</div>';
// check blacklist file is_writable
} else if (defined('BLACKLIST') && !is_readable(BLACKLIST)) {
    echo '<div class="highlight-1">'._("Error : the blacklist file cannot be read. Please contact the admin").'</div>';
// check aliasdeny file is_writable
} else if (defined('ALIASDENY') && !is_readable(ALIASDENY)) {
    echo '<div class="highlight-1">'._("Error : the forbidden aliases file cannot be read. Please contact the admin").'</div>';
// maintenance mod
} else if (MAINTENANCE_MODE == true && MAINTENANCE_IP != $_SERVER["REMOTE_ADDR"]) {
	echo '<div class="highlight-2">'._("Service under maintenance").'</div>';
} else {

if (MAINTENANCE_MODE == true) {
	echo '<div class="highlight-2">'._("Service under maintenance").'</div>';
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
	die('._("Connexion à la base ").'.$e->getMessage());
}
// Create DB if not exists
try {
	// status : 0=not verified - 3=disable - 5=active
	if (preg_match('/^sqlite/', DB)) {
		$create = $dbco->query("CREATE TABLE IF NOT EXISTS ".DBTABLEPREFIX."alias (
								id INTEGER PRIMARY KEY,
								status INTEGER(1) NOT NULL,
								alias CHAR(150) NOT NULL UNIQUE,
								email CHAR(150) NOT NULL,
								dateCreat DATETIME NOT NULL,
								dateExpir DATETIME,
								comment TEXT);");
	} else {
		$create = $dbco->query("CREATE TABLE IF NOT EXISTS ".DBTABLEPREFIX."alias (
								id INTEGER PRIMARY KEY  AUTO_INCREMENT,
								status INTEGER(1) NOT NULL,
								alias CHAR(150) NOT NULL UNIQUE,
								email CHAR(150) NOT NULL,
								dateCreat DATETIME NOT NULL,
								dateExpir DATETIME,
								comment TEXT);");
	}
} catch ( PDOException $e ) {
	echo '<div class="highlight-1">'._("Error initializing tables. Please contact the admin");
	if (DEBUG) { $e->getMessage(); }
	echo '</div>';
	die();
}

//////////////////
// Start program
//////////////////

// get process "act" (action)
$action = isset($_GET['act']) ? $_GET['act'] : '';
switch ($action) {
	case "validemail" :
		$get_value = urlUnGen($_GET['value']);
		if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE id = '".$get_value['id']."' AND status = 0")->fetchColumn() != 0) {
			UpdateStatusAlias($get_value['id'], $get_value['alias_full'], 5);
			echo '<div class="highlight-3">'._("Your trash email address").' <b>'.$get_value['alias_full'].'</b> '._("is now enabled").'</div>';
		} else {
			echo '<div class="highlight-1">'._("Error : unknown ID or already validated").'</div>';
		}
	break;
	case "disable" :
		$get_value = urlUnGen($_GET['value']);
		DisableAlias($get_value['id'], $get_value['alias_full'], null);
	break;
	case "enable" :
		$get_value = urlUnGen($_GET['value']);
		EnableAlias($get_value['id'], $get_value['alias_full'], null);
	break;
	case "delete" :
		$get_value = urlUnGen($_GET['value']);
		DeleteAlias($get_value['id'], $get_value['alias_full']);
	break;
	case "cron" :
		if (CRON) {
			echo '<div class="highlight-2">'._("The scheduled task is running").'</div>';
			LifeExpire();
		} else {
			echo '<div class="highlight-1">'._("You didn't allow the scheduled job").'</div>';
		}
	break;
}
// Form
if (isset($_POST['username']) && $_POST['username'] != '') { // minimal anti-spam 
	echo 'Hello you';
} else if (isset($_POST['list'])) {
	$email=strtolower(StripCleanToHtml($_POST['email']));
	if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">'._("Error : email address incorrect").'</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">'._("Error : email address incorrect").'  (2)</div>';
	} else if (ListeAlias($email)) {
		echo '<div class="highlight-3">'._("An email has been sent to you").'</div>';
	} else {
		echo '<div class="highlight-1">'._("Error : no known active trash email").'</div>';
	}
} else if (isset($_POST['email']) && isset($_POST['alias'])) {
	$alias=strtolower(StripCleanToHtml($_POST['alias']));
	$email=strtolower(StripCleanToHtml($_POST['email']));
	$domain=StripCleanToHtml($_POST['domain']);
	$life=$_POST['life'];
	$comment=StripCleanToHtml($_POST['comment']);
	$alias_full=$alias.'@'.$domain;
	// Check form
	if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="highlight-1">'._("Error : email address incorrect").'</div>';
	} else if (! VerifMXemail($email)) {
		echo '<div class="highlight-1">'._("Error : email address incorrect").' (2)</div>';
	} else if (! preg_match('#^[\w.-]+$#',$alias)) {
		echo '<div class="highlight-1">'._("Error : trash email address syntax incorrect").'</div>';
	} else if (!domainePresent($domain)) {
		echo '<div class="highlight-1">'._("Error : this domain cannot be used").'</div>';
	} else if (emailIsAlias($email)) {
		echo '<div class="highlight-1">'._("Error : Your email can not contain a trash domain").'</div>';
	} else if (AliasDeny($alias)) {
		echo '<div class="highlight-1">'._("Error : trash email address forbidden").'</div>';
	} else if (BlacklistEmail($email)) {
		echo '<div class="highlight-1">'._("Error : you have been blacklisted").'</div>';
	// add 
	} elseif (isset($_POST['add'])) {
		if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE alias = '".$alias_full."'")->fetchColumn() != 0) {
			echo '<div class="highlight-1">'._("Error : this trash email already exists").'</div>';
		} else if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE email = '".$email."'")->fetchColumn() > ALIASLIMITBYMAIL) {
			echo '<div class="highlight-1">'._("Error: You have reached your limit quota on this app. You can <a href=\"http://forge.zici.fr/p/emailpoubelle-php/\"> install this script </a> on a personal server if you want more quota").'.</div>';
		} else {
			if ($dbco->query("SELECT COUNT(*) FROM ".DBTABLEPREFIX."alias WHERE email = '".$email."' AND status > 0")->fetchColumn() != 0) {
				AjouterAlias(5, $alias_full, $email, $life, $comment);
				echo '<div class="highlight-3">'._("Your trash email address").'<b> '.$alias_full.' > '.$email.'</b> '._("is now enabled").'</div>';
			} else {
				$lastId=AjouterAlias(0, $alias_full, $email, $life, $comment);
				$message= _("Confirmation of the creation of your trash email :")."\n";
				$message= $alias_full.' => '.$email."\n";
				$message= _("Click on the link below to validate :")."\n";
				$message.= "\t * ".urlGen('validemail',$lastId,$alias_full)."\n";
				$message.= "\n";
				$message.= _("To delete this trash email, click on the link below :")."\n";
				$message.= "\t * ".urlGen('delete',$lastId,$alias_full)."\n";
				$message.= "\n";
				$message.= _("After confirmation, you will be able to temporary suspend you trash email using the link below :")."\n";
				$message.= "\t * ".urlGen('disable',$lastId,$alias_full)."\n";
				SendEmail($email,_("Alias confirmation")." ".$alias,$message);
				echo '<div class="highlight-2">'._("Your email address").' ('.$email.') '._("is unknown, a confirmation has been sent to you.").'</div>';
			}
		}
	// delete
	} else if (isset($_POST['del'])) {
		if ($id = $dbco->query("SELECT id FROM ".DBTABLEPREFIX."alias WHERE email = '".$email."' AND alias = '".$alias_full."'")->fetchColumn()) {
			$message= _("Confirmation of the removal of your trash email : ")."\n";
			$message= $alias_full.' => '.$email."\n";
			$message= _("Click on the link below to validate the deletion :")."\n";
			$message.= "\t * ".urlGen('delete',$id,$alias_full)."\n\n";
			$message.= _("If you would like to temporary suspend this trash email, you can follow the link bellow :")."\n";
			$message.= "\t * ".urlGen('disable',$id,$alias_full)."\n";
			SendEmail($email,_("Alias deletion")." ".$alias,$message);
			echo '<div class="highlight-2">'._("An email has been sent to you").'.</div>';
		} else {
			echo '<div class="highlight-1">'._("Error : unknown trash email").'</div>';
		}
	// disable
	} else if (isset($_POST['disable'])) {
		DisableAlias(null, $alias_full, $email);
	// enable
	} else if (isset($_POST['enable'])) {
		EnableAlias(null, $alias_full, $email);
	}

	// memory email
	if (isset($_POST['memory'])) {
		setcookie ("email", StripCleanToHtml($email), time() + 31536000);
	} else if (isset($_COOKIE['email'])) {
		unset($_COOKIE['email']);
	}
}

//////////////////
// Printing form
//////////////////

?>
<?php languesSwitch(); ?>

<form action="<?= URLPAGE?>" method="post">

<div id="onglet" style="display: none;">
	<input type="button" value=<?php echo _("Add") ?> id="onglet-add" onClick="ongletChange(this.id)" /> 
	<input type="button" id="onglet-list" value=<?php echo _("List") ?> onClick="ongletChange(this.id)" /> 
	<input type="button" id="onglet-del" value=<?php echo _("Delete") ?> onClick="ongletChange(this.id)" /> 
	<input type="button" id="onglet-dis" value=<?php echo _("Suspend") ?> onClick="ongletChange(this.id)" />
	<input type="button" id="onglet-en" value=<?php echo _("Resume") ?> onClick="ongletChange(this.id)" />
	<input type="hidden" name="onglet-actif" id="onglet-actif" value="onglet-add" />
</div>
<div id="form-email">
	<label for="email"><?php echo _("Your real email address") ?> : </label>
	<input type="text" name="email" <?php if (isset($_COOKIE['email'])) { echo 'value="'.$_COOKIE['email'].'"'; } ?> id="input-email" size="24" border="0"  onkeyup="printForm()" onchange="printForm()"  /> 
	<input class="button2" type="submit" name="list" id="button-list" value="Lister" />
	<input type="checkbox" name="memory" id="check-memory" <?php if (isset($_COOKIE['email'])) { echo 'checked="checked" '; } ?>/> <?php echo _("Remember")?>
</div>
<div id="form-alias">
	<label for="alias"><?php echo _("Name of your trash email address")?> : </label>
	<input type="text" name="alias" id="input-alias" size="24" border="0" onkeyup="printForm()" onchange="printForm()" placeholder=<?php echo _("Ex : john_shop") ?>/> @<?php
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
		<option value="0"><?php echo _("Unlimited time")?></option>
		<option value="7200"><?php echo _("2 hours")?></option>
		<option value="21600"><?php echo _("6 hours")?></option>
		<option value="86400"><?php echo _("1 day")?></option>
		<option value="604800"><?php echo _("7 days")?></option>
		<option value="1296000"><?php echo _("15 days")?></option>
		<option value="2592000"><?php echo _("30 days")?></option>
		<option value="7776000"><?php echo _("90 days")?></option>
	</select>
</div>
<div id="form-comment">
	<label for="comment"><?php echo _("Comment for this trash email (for your to remember)")?></label>
	<input type="text" name="comment" size="54" placeholder=<?php echo _("Ex : Inscription sur zici.fr") ?>/>
</div>
<div id="form-submit">
	<input class="button" type="submit" id="button-add" name="add" value=<?php echo _("Activate") ?> />
	<input class="button" type="submit" id="button-del" name="del" value=<?php echo _("Delete") ?> />
	<input class="button" type="submit" id="button-enable" name="enable" value=<?php echo _("Suspend") ?> />
	<input class="button" type="submit" id="button-disable" name="disable" value=<?php echo _("Resume") ?> />
</div>
<div id="lePecheur" style="display: none;">
	<input name="username" type="text" />
</div>
</form>

<script type="text/javascript">
	function validateEmail(email) { 
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(email);
	} 
	function printForm() {
		if (validateEmail(document.getElementById('input-email').value) && document.getElementById('input-alias').value != '') {
			document.getElementById('input-alias').disabled = false; 
			document.getElementById('input-domain').disabled = false; 
			document.getElementById('button-list').disabled = false; 
			document.getElementById('button-add').disabled = false; 
			document.getElementById('button-disable').disabled = false; 
			document.getElementById('button-enable').disabled = false; 
			document.getElementById('button-del').disabled = false; 
			document.getElementById('input-life').disabled = false; 
			if (document.getElementById('onglet-actif').value == 'onglet-add') {
				document.getElementById('form-comment').style.display = "block" ;
			}
		} else if (validateEmail(document.getElementById('input-email').value)) {
			document.getElementById('input-alias').disabled = false; 
			document.getElementById('input-domain').disabled = false; 
			document.getElementById('button-list').disabled = false;
			document.getElementById('input-life').disabled = false;
			document.getElementById('form-comment').style.display = "display" ;
			document.getElementById('button-add').disabled = true; 
			document.getElementById('button-disable').disabled = true; 
			document.getElementById('button-enable').disabled = true; 
			document.getElementById('button-del').disabled = true; 
			document.getElementById('input-life').disabled = true;
			document.getElementById('form-comment').style.display = "none" ;
		} else {
			document.getElementById('input-alias').disabled = true; 
			document.getElementById('input-domain').disabled = true; 
			document.getElementById('button-list').disabled = true; 
			document.getElementById('button-add').disabled = true; 
			document.getElementById('button-disable').disabled = true; 
			document.getElementById('button-enable').disabled = true; 
			document.getElementById('button-del').disabled = true; 
			document.getElementById('input-life').disabled = true;
			document.getElementById('form-comment').style.display = "none" ;
		}
	}
	function ongletPrint() {
		var ongletActif = document.getElementById('onglet-actif').value;
		document.getElementById('onglet-add').className = "close" ;
		document.getElementById('onglet-del').className = "close" ;
		document.getElementById('onglet-list').className = "close" ;
		document.getElementById('onglet-en').className = "close" ;
		document.getElementById('onglet-dis').className = "close" ;
		document.getElementById(ongletActif).className = "open" ;
		document.getElementById('input-life').style.display = "none" ;
		document.getElementById('form-alias').style.display = "inline-block" ;
		document.getElementById('button-add').style.display = "none" ;
		document.getElementById('button-del').style.display = "none" ;
		document.getElementById('button-list').style.display = "none" ;
		document.getElementById('button-disable').style.display = "none" ;
		document.getElementById('button-enable').style.display = "none" ;
		if (ongletActif == 'onglet-add') {
			document.getElementById('button-add').style.display = "inline-block" ;
			document.getElementById('input-life').style.display = "inline-block" ;
		} else if (ongletActif == 'onglet-del') {
			document.getElementById('button-del').style.display = "inline-block" ;
		} else if (ongletActif == 'onglet-en') {
			document.getElementById('button-enable').style.display = "inline-block" ;
		} else if (ongletActif == 'onglet-dis') {
			document.getElementById('button-disable').style.display = "inline-block" ;
		} else if (ongletActif == 'onglet-list') {
			document.getElementById('button-list').style.display = "inline-block" ;
			document.getElementById('form-alias').style.display = "none" ;
		}
	}
	function ongletChange(ongletValue) {
		document.getElementById('onglet-actif').value = ongletValue;
		ongletPrint();
	}
	document.getElementById('onglet').style.display = "block" ;
	ongletPrint();
	printForm();
</script>
<p><?php echo _("Version")?> <?= VERSION ?> - <?php echo _("Created by David Mercereau under licence GNU GPL v3")?></p>
<p><?php echo _("Download and use this script on the project website")?> <a target="_blank" href="https://framagit.org/kepon/emailPoubellePhp/">emailPoubelle.php</a></p>

<?php 
// execute lifeExpir if isn't in crontab
if (!CRON) { LifeExpire(); }
	// Close connexion DB
	$dbco = null;
	// checkupdate
	echo CheckUpdate(); 
} // end maintenance mod
?>
