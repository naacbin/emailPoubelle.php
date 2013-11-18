emailPoubelle.php
=============

Un script pour un petit service d'email jetable

* [Démo](http://poubelle.zici.fr/)
* [Page du projet](http://forge.zici.fr/p/emailpoubelle-php/)
* [Download](http://forge.zici.fr/p/emailpoubelle-php/source/download/master/)
* [Bug report](http://forge.zici.fr/p/emailpoubelle-php/issues/)

Installation
-----------

Installation des dépendances :

    pear install Net_DNS2

Télécharger & décompresser les sources :

    mkdir -p /www/emailPoubelle/postfix
    cd /tmp
    wget -O emailPoubelle.zip http://forge.zici.fr/p/emailpoubelle-php/source/download/master/
    unzip emailPoubelle.zip
    cp emailpoubelle-php-master/* /www/emailPoubelle

Configurer Postfix :

    vi /etc/postfix/main.cf
        [...]
        virtual_alias_maps = hash:/www/emailPoubelle/postfix/virtual
    touch /www/emailPoubelle/postfix/virtual
    /usr/sbin/postmap /www/emailPoubelle/postfix/virtual
    chown www-data /www/emailPoubelle/postfix/virtual
    chown www-data /www/emailPoubelle/postfix/virtual.db

Ajouter dans le fichier /etc/alias le devnull

	echo "devnull:	/dev/null" >> /etc/aliases
	newaliases
