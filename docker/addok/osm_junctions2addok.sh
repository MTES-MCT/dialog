#!/bin/sh -eu

# MIT License
# Florimond Manca, 2023
# Adapted from: https://gist.github.com/cquest/c0a84e6757d15e66e6ae429e91a74a9e

mkdir -p data/download

echo "> Création d'une base de données d'import..."

createdb osm_junctions2addok
psql -d osm_junctions2addok -c 'create extension postgis;'

echo "--> Import des communes..."

echo "-----> Téléchargement du fichier ADMIN-EXPRESS..."
# See: https://geoservices.ign.fr/adminexpress
cd data/download
wget -N -nv --show-progress https://wxs.ign.fr/x02uy2aiwjo9bm8ce5plwqmr/telechargement/prepackage/ADMINEXPRESS_SHP_WGS84G_PACK_2023-07-04\$ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/file/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z
cd ../../

echo "-----> Décompression du fichier ADMIN-EXPRESS..."
7zr e -odata data/download/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z "ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/ADMIN-EXPRESS/1_DONNEES_LIVRAISON_2023-07-04/ADE_3-2_SHP_WGS84G_FRA/COMMUNE.*"

echo "-----> Import de la classe COMMUNE..."
# Docs on ogr2ogr: https://gdal.org/programs/ogr2ogr.html
# Docs on the 'pgdump' format for ogr2ogr: https://gdal.org/drivers/vector/pgdump.html
ogr2ogr -f pgdump /vsistdout/ data/COMMUNE.shp -nln communes -nlt MULTIPOLYGON --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d osm_junctions2addok

echo "--> Import des routes..."

echo "----> Téléchargement du fichier OSM..."
# Source: https://download.geofabrik.de/index.html
mkdir -p data/osm-ile-de-france
cd data/download
wget -N -nv --show-progress https://download.geofabrik.de/europe/france/ile-de-france-latest-free.shp.zip
cd ../../

echo "----> Décompression du fichier OSM..."
unzip -u -d data/osm-ile-de-france data/download/ile-de-france-latest-free.shp.zip

echo "----> Import de osm_roads..."
ogr2ogr -f pgdump /vsistdout/ data/osm-ile-de-france/gis_osm_roads_free_1.shp -nln osm_roads --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d osm_junctions2addok

echo "---> Calcul et export des intersections..."

psql -d osm_junctions2addok -tA -c "
SELECT row_to_json(p) FROM
(
    SELECT j.*, c.nom as city, c.insee_com as citycode
    FROM (SELECT 'junction_' || st_geohash(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0001)))) as id,
        'poi' as type,
        'junction' as poi,
        format('%s / %s', r1.name, r2.name) as name,
        st_y(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0005)))) as lat,
        st_x(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0005)))) as lon,
        '' as context,
        0 as rank
    FROM osm_roads r1
    JOIN osm_roads r2
        on (st_intersects(r1.geometry, r2.geometry))
    WHERE
        r1.name is not null and length(r1.name) > 3
        and r2.name is not null and length(r2.name) > 3
        and r1.name < r2.name
    GROUP BY r1.name, r2.name) as j
    JOIN communes c ON (ST_intersects(st_setsrid(st_makepoint(lon,lat),4326), geometry))
) as p" > junctions.json

echo "---> Nettoyage..."

dropdb osm_junctions2addok

echo "Terminé"
