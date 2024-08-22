# Zones JOP

## `zones.geojson`
Fichier GeoJSON créé comme suit:

1. Télécharger sur le [kDrive (Dossier partagé > JO > Périmètres JOP - Données Préfecture de police)](https://kdrive.infomaniak.com/app/drive/184671/files/54928) les 5 fichiers Shapefile des zones JOP : .shp, .dbf, .prj, .cpg et .shx
2. Les placer dans un même dossier, par exemple `~/Documents/jop`
3. Lancer le script de conversion Shapefile vers GeoJSON

```bash
./tools/jop_prepare.sh ~/Documents/jop/NOM_DU_FICHIER.shp
```

Ce script génère un fichier `data/jop/zones.geojson`.

## `para_ouverture.geojson`

Fichier GeoJSON pour la cérémonie d'ouverture des Paralympiques

Le Shapefile provient de : https://github.com/MTES-MCT/dialog/issues/923#issue-2480179238

Le GeoJSON a été obtenu avec `./tools/ghp2geojson.py`

Les attributs `DATE_DEBUT` et `DATE_FIN` étaient absents ils ont été ajoutés manuellement à l'aide de la [carte interactive](https://anticiperlesjeux.gouv.fr/carte-interactive-impacts-deplacements-ile-france).
