emailPoubelle.php
=============

Un script pour un petit service d'email jetable

* [Démo](http://poubelle.zici.fr/)
* [Page du projet](https://forge.zici.fr/project/view/6/)
* [Download/source](https://forge.zici.fr/source/emailpoubelle.php/)

Installation
-----------

Installation des dépendances :

    pear install Net_DNS2

Télécharger & décompresser les sources :

    mkdir -p /www/emailPoubelle/postfix
    cd /tmp
    wget -O emailPoubelle.zip https://framagit.org/kepon/emailPoubellePhp/-/archive/master/emailPoubellePhp-master.zip
    unzip emailPoubelle.zip
    cp -r emailpoubelle-php-master/* /var/www/emailPoubelle

Configure apache virtualhost (ou autres serveur http)
	[...]
	DocumentRoot /var/www/emailPoubelle/www
	[...]

Configurer Postfix :

    vi /etc/postfix/main.cf
        [...]
        virtual_alias_maps = hash:/www/emailPoubelle/var/virtual
    touch /www/emailPoubelle/var/virtual
    /usr/sbin/postmap /www/emailPoubelle/var/virtual
    chown www-data /www/emailPoubelle/var/virtual
    chown www-data /www/emailPoubelle/var/virtual.db

Ajouter dans le fichier /etc/aliases le devnull

	echo "devnull:	/dev/null" >> /etc/aliases
	newaliases
