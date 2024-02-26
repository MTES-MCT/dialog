# BAC-IDF

## Importer les arrêtés

### Préparation

* Récupérer le [fichier `decrees.json` sur le kDrive](https://kdrive.infomaniak.com/app/drive/184671/files/26737) puis le placer dans `data/bac_idf/decrees.json`.

### Exécution

Lancer cette commande pour importer les arrêtés de `decrees.json` dans DiaLog :

```bash
make data_bac_idf_import
```

## Création du fichier decrees.json

Le dump de BAC-IDF est fourni sous format compressé `.bson`. Il faut l'importer dans une base MongoDB puis le réexporter en JSON non-compressé.

Pour cela, lancer la commande :

```bash
make data/decrees.json
```

Cela créera le fichier `data/decrees.json`.
