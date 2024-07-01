# Zones JOP

Fichier GeoJSON créé comme suit:

1. Télécharger sur le [kDrive (Dossier partagé > JO > Périmètres JOP - Données Préfecture de police)](https://kdrive.infomaniak.com/app/drive/184671/files/54928) les 5 fichiers Shapefile des zones JOP : .shp, .dbf, .prj, .cpg et .shx
2. Les placer dans un même dossier, par exemple `~/Documents/jop`
3. Lancer le script de conversion Shapefile vers GeoJSON

```bash
./tools/jop_prepare.sh ~/Documents/jop/NOM_DU_FICHIER.shp
```

Ce script génère un fichier `data/jop/zones.geojson`.
