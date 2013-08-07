#!/bin/bash

# @todo : A adapter en php 

FICHIERALIAS="./postfix/virtual"

echo 
echo "#### Statistique emailJetable.php ####"
echo 
echo "Nombre d'email validé : `grep -v "^#" $FICHIERALIAS | wc -l`
echo "Nombre d'email n'ayant pas été validé : `grep "^#" $FICHIERALIAS | wc -l`
echo
echo "Top 30 :"
grep -v "^#" $FICHIERALIAS | cut -d" " -f2 | sort | uniq -c | sort -nr | head -n 30
echo
