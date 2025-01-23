# Aires de stockage

Pour les arrêtés de [viabilité hivernale](https://github.com/MTES-MCT/dialog/issues/990), DiaLog a besoin des données sur l'emplacement des aires de stockage où les poids lourds doivent attendre que la barrière de dégel soit levée.

L'application DiaLog récupère ces données dans la table `storage_area`.

Cette table est remplie avec des données issues d'un fichier CSV fourni par la DGITM.

## Chargement des données

Il n'y a rien à faire, les données sont présentes dans une migration et sont donc importées dans `storage_area` lors d'un `make install`.

## Mise à jour des données

S'il y a lieu de mettre à jour la base d'aires de stockage, alors :

1. Remplacez `data/aires_de_stockage.csv` par le nouveau fichier CSV
2. Lancez `make -B data/aires_de_stockage.php`. Cela génère un fichier `data/aires_de_stockage.php` et une migration vide.
3. Copiez-collez la ligne de PHP présente dans `data/aires_de_stockage.php` dans le `up()` de la nouvelle migration. 
    > Le SQL généré fait des upserts (`INSERT ... ON CONFLICT (source_id) DO UPDATE ...`) pour préserver les associations entre les `Location` et les `StorageArea` existants.
4. Faites une PR avec la nouvelle migration.

