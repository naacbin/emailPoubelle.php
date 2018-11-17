<?php
$country = @geoip_country_code_by_name(get_ip());

$HTTP_ACCEPT_LANGUAGE=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
$lang_from_http_accept = explode(',', $HTTP_ACCEPT_LANGUAGE);
$lang = $lang_from_http_accept[0];

// Dans les URL on utilisera les codes langues https://support.crowdin.com/api/language-codes/
// On a une fonction pour retrouve le local à partir (et vis et versa)

//							CODE			LOCALE (locale -a)
$langueEtLocalDispo=array(
							'fr'		=> 'fr_FR', 
							'en'		=> 'en_US', 
							'it'		=> 'it_IT', 
							);


// Détection et redirection (langue toujours)
if (isset($_POST['langSwitch'])) {
	$locale = lang2locale($_POST['langSwitch']);
	$localeshort=locale2lang($locale);
	if ($_COOKIE['langue'] != $localeshort) {
		setcookie("langue",$localeshort,strtotime( '+1 year' ));
	}
} elseif (isset($_COOKIE['langue'])) {
	$locale = lang2locale($_COOKIE['langue']);
	$lang=locale2lang($locale);
	//header('Location: '.addLang2url($lang));
} else {
	$HTTP_ACCEPT_LANGUAGE=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	//echo $HTTP_ACCEPT_LANGUAGE.'<br />';
	$lang_from_http_accept = explode(',', $HTTP_ACCEPT_LANGUAGE);
	//echo $lang_from_http_accept[0].'<br />';
	$locale = lang2locale($lang_from_http_accept[0]);
	if (substr($locale,0,2) != substr($lang_from_http_accept[0],0,2)) {
		//echo "Non trouvé, 2ème tentative";
		$lang_from_http_accept = explode('-', $lang_from_http_accept[0]);
		//echo $lang_from_http_accept[0].'<br />';
		$locale = lang2locale($lang_from_http_accept[0]);
	}
	//echo $locale.'<br />';
	$lang = locale2lang($locale);
	//echo $lang.'<br />';
}

// Définition de la langue :
$results=putenv("LC_ALL=$locale.utf8");
if (!$results) {
    exit ('putenv failed');
}
$results=putenv("LC_LANG=$locale.utf8");
if (!$results) {
    exit ('putenv failed');
}
$results=putenv("LC_LANGUAGE=$locale.utf8");
if (!$results) {
    exit ('putenv failed');
}
$results=setlocale(LC_ALL, "$locale.utf8");
if (!$results) {
    exit ('setlocale failed: locale function is not available on this platform, or the given local does not exist in this environment');
}
bindtextdomain("messages", LANG);
textdomain("messages");

?>
