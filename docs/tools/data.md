# Données

## Liste des communes

DiaLog utilise la liste des communes publiée au format JSON par [etalab/decoupage-administratif](https://github.com/etalab/decoupage-administratif).

Le fichier JSON est traité pour produire un fichier SQL qui peut ensuite être importé dans la base de données à l'aide d'une commande Symfony personnalisée.

Pour importer les données, utiliser :

```bash
make data_install
```

Pour mettre à jour les données sources, lancer :

```bash
make data_update
make data_install # Pour recharger dans la base de données
```
