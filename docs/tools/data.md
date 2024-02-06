# Données

## Liste des communes

DiaLog utilise la liste des communes publiée au format JSON par [etalab/decoupage-administratif](https://github.com/etalab/decoupage-administratif).

Le fichier JSON est traité pour produire un fichier SQL est ensuite importé dans la base de données à l'aide d'une commande Symfony personnalisée.

* Lors du dev, l'import est fait automatiquement lors de `make install`.
* En production, l'import étant suffisamment rapide (moins d'une seconde), il est fait à chaque déploiement.

Pour importer les données manuellement, utiliser :

```bash
make data_install
```

Pour mettre à jour les données sources, lancer :

```bash
make data_update
make data_install # Pour recharger dans la base de données
```

## Liste des gestionnaires de routes départementales

Pour le type de localisation "Route départementale", DiaLog récupère la liste auprès de la BD TOPO.

Malheureusement cette liste ne peut pas être récupérée dynamiquement avec l'API WFS de l'IGN. La seule source existante est la documentation PDF de la BD TOPO.

On stocke un copier-coller de la liste dans un fichier `data/gestionnaires.txt` qui est ensuite traité en PHP. Cela réduit le besoin de formatage manuel.

Pour mettre à jour cette liste, ouvrir la dernière version de la [documentation](https://geoservices.ign.fr/documentation/donnees/vecteur/bdtopo) (fichier "Descriptif du contenu"), puis :

* Aller à la section "Route numérotée ou nommée > Type de route = « Départementale » > Valeurs du champ « Gestionnaire » associées" (page 300)
* Copier-coller la liste dans `data/gestionnaires.txt`
* Retirer la valeur "Sans valeur"
