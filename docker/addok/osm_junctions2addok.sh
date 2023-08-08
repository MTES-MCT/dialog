#!/bin/bash -eu

# MIT License
# Florimond Manca, 2023
# Adapted from: https://gist.github.com/cquest/c0a84e6757d15e66e6ae429e91a74a9e

function cleanup() {
    echo "--> Nettoyage..."
    dropdb --if-exists osm_junctions2addok
}

function prepare() {
    mkdir -p data/download

    echo "--> Préparation..."

    echo "-----> Création d'une base de données d'import..."
    createdb osm_junctions2addok
    psql -d osm_junctions2addok -c 'create extension postgis;'
}

function import_communes() {
    echo "--> Import des communes..."

    echo "-----> Téléchargement du fichier ADMIN-EXPRESS..."
    # See: https://geoservices.ign.fr/adminexpress
    pushd data/download
    wget -N -nv --show-progress https://wxs.ign.fr/x02uy2aiwjo9bm8ce5plwqmr/telechargement/prepackage/ADMINEXPRESS_SHP_WGS84G_PACK_2023-07-04\$ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/file/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z
    popd

    # echo "-----> Décompression du fichier ADMIN-EXPRESS..."
    7zr e -odata/download data/download/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z "ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/ADMIN-EXPRESS/1_DONNEES_LIVRAISON_2023-07-04/ADE_3-2_SHP_WGS84G_FRA/COMMUNE.*"
    7zr e -odata/download data/download/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z "ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/ADMIN-EXPRESS/1_DONNEES_LIVRAISON_2023-07-04/ADE_3-2_SHP_WGS84G_FRA/ARRONDISSEMENT_MUNICIPAL.*"

    echo "-----> Import de la classe COMMUNE..."
    # Docs on ogr2ogr: https://gdal.org/programs/ogr2ogr.html
    # Docs on the 'pgdump' format for ogr2ogr: https://gdal.org/drivers/vector/pgdump.html
    ogr2ogr -f pgdump /vsistdout/ data/download/COMMUNE.shp -nln communes -nlt MULTIPOLYGON --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d osm_junctions2addok

    echo "-----> Import de la classe ARRONDISSEMENT_MUNICIPAL..."
    # Docs on ogr2ogr: https://gdal.org/programs/ogr2ogr.html
    # Docs on the 'pgdump' format for ogr2ogr: https://gdal.org/drivers/vector/pgdump.html
    ogr2ogr -f pgdump /vsistdout/ data/download/ARRONDISSEMENT_MUNICIPAL.shp -nln arrondissements_municipaux -nlt MULTIPOLYGON --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d osm_junctions2addok
}

function import_codes_postaux() {
    echo "--> Import du fichier des codes postaux..."

    echo "-----> Préparation de la table..."
    psql -d osm_junctions2addok -c "CREATE TABLE codes_communes (code_insee CHAR(5) PRIMARY KEY, code_postal CHAR(5) NOT NULL);"

    echo "-----> Téléchargement du fichier..."
    # See: https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/
    pushd data/download
    wget -N -nv --show-progress https://www.data.gouv.fr/fr/datasets/r/5ed9b092-a25d-49e7-bdae-0152797c7577
    popd

    echo "-----> Préparation de l'import..."
    cat data/download/019HexaSmal.csv |
        # Ne garde que les colonnes code insee et code postal
        cut --delimiter=";" --fields="1,3" - |
        # Déduplique les communes présentes plusieurs fois en raison de multiples appellations ou lieux-dits
        awk -F ';' '{key = $1} !seen[key]++' - > data/download/codes_communes.csv

    echo "-----> Import des codes postaux..."
    cat 'data/download/codes_communes.csv' | psql -d osm_junctions2addok -c "COPY codes_communes (code_insee, code_postal) FROM STDIN with (DELIMITER ';', FORMAT csv, HEADER);" 
}

function import_roads() {
    echo "--> Import des routes..."

    echo "-----> Téléchargement du fichier OSM..."
    # Source: https://download.geofabrik.de/index.html
    pushd data/download
    wget -nc --show-progress https://download.geofabrik.de/europe/france/ile-de-france-latest-free.shp.zip
    popd

    echo "-----> Décompression du fichier OSM..."
    mkdir -p data/download/osm-idf
    unzip -u -n -d data/download/osm-idf data/download/ile-de-france-latest-free.shp.zip

    echo "-----> Import de osm_roads..."
    ogr2ogr -f pgdump /vsistdout/ data/download/osm-idf/gis_osm_roads_free_1.shp -nln osm_roads --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d osm_junctions2addok
}

function compute_junctions() {
    echo "--> Calcul et export des intersections..."

    psql -d osm_junctions2addok -tA -c "
    SELECT row_to_json(p) FROM
    (
        SELECT j.*, c.nom as city, cc.code_postal as postcode
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
        JOIN (
            (SELECT geometry, nom, insee_com AS code_insee FROM communes)
            UNION ALL
            (SELECT geometry, nom, insee_arm AS code_insee FROM arrondissements_municipaux)
        ) c ON (ST_intersects(st_setsrid(st_makepoint(lon,lat),4326), geometry))
        JOIN codes_communes cc ON cc.code_insee = c.code_insee
    ) as p" > data/junctions.json
}

cleanup
prepare
import_communes
import_codes_postaux
import_roads
compute_junctions
echo "Terminé"
