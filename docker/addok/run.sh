#!/bin/bash -e

cd /data
mkdir -p download

# Adapted from: https://gist.github.com/cquest/c0a84e6757d15e66e6ae429e91a74a9e

TEMP_DB=osm_junctions2addok
ADMIN_EXPRESS_URL=https://wxs.ign.fr/x02uy2aiwjo9bm8ce5plwqmr/telechargement/prepackage/ADMINEXPRESS_SHP_WGS84G_PACK_2023-07-04\$ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/file/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z
CODES_POSTAUX_URL=https://www.data.gouv.fr/fr/datasets/r/5ed9b092-a25d-49e7-bdae-0152797c7577
OSM_DATA_URL=https://download.geofabrik.de/europe/france/ile-de-france-latest-free.shp.zip

function cleanup() {
    echo "--> Nettoyage..."
    dropdb --if-exists $TEMP_DB
}

function download() {
    echo "--> Téléchargement des fichiers..."

    if [ -z $NO_DOWNLOAD ]; then
        echo "-----> Téléchargement de ADMIN-EXPRESS..."
        # See: https://geoservices.ign.fr/adminexpress
        pushd download
        wget -N -nv --show-progress $ADMIN_EXPRESS_URL
        popd
    fi

    echo "-----> Décompression de ADMIN-EXPRESS..."
    7z e -aos -odownload download/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z "ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/ADMIN-EXPRESS/1_DONNEES_LIVRAISON_2023-07-04/ADE_3-2_SHP_WGS84G_FRA/COMMUNE.*"
    7z e -aos -odownload download/ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04.7z "ADMIN-EXPRESS_3-2__SHP_WGS84G_FRA_2023-07-04/ADMIN-EXPRESS/1_DONNEES_LIVRAISON_2023-07-04/ADE_3-2_SHP_WGS84G_FRA/ARRONDISSEMENT_MUNICIPAL.*"

    if [ -z $NO_DOWNLOAD ]; then
        echo "-----> Téléchargement du fichier des codes postaux..."
        # See: https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/
        pushd download
        wget -N -nv --show-progress $CODES_POSTAUX_URL
        popd
    fi
}

function make_junctions_json() {
    echo "--> Création du fichier junctions.json..."

    echo "-----> Création d'une base de données d'import..."
    createdb $TEMP_DB
    psql -d $TEMP_DB -c 'create extension postgis;'

    ###
    ###
    echo "--> Import des communes..."

    echo "-----> Import de la classe COMMUNE..."
    # Docs on ogr2ogr: https://gdal.org/programs/ogr2ogr.html
    # Docs on the 'pgdump' format for ogr2ogr: https://gdal.org/drivers/vector/pgdump.html
    ogr2ogr -f pgdump /vsistdout/ download/COMMUNE.shp -nln communes -nlt MULTIPOLYGON --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d $TEMP_DB

    echo "-----> Import de la classe ARRONDISSEMENT_MUNICIPAL..."
    # Docs on ogr2ogr: https://gdal.org/programs/ogr2ogr.html
    # Docs on the 'pgdump' format for ogr2ogr: https://gdal.org/drivers/vector/pgdump.html
    ogr2ogr -f pgdump /vsistdout/ download/ARRONDISSEMENT_MUNICIPAL.shp -nln arrondissements_municipaux -nlt MULTIPOLYGON --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d $TEMP_DB

    ###
    ###
    echo "--> Import du fichier des codes postaux..."

    echo "-----> Préparation de la table codes_communes..."
    psql -d $TEMP_DB -c "CREATE TABLE codes_communes (code_insee CHAR(5) PRIMARY KEY, code_postal CHAR(5) NOT NULL);"

    echo "-----> Téléchargement du fichier des codes postaux..."
    # See: https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/
    pushd download
    wget -N -nv --show-progress https://www.data.gouv.fr/fr/datasets/r/5ed9b092-a25d-49e7-bdae-0152797c7577
    popd

    echo "-----> Préparation du fichier des codes postaux..."
    cat download/019HexaSmal.csv |
        # Ne garde que les colonnes code insee et code postal
        cut --delimiter=";" --fields="1,3" - |
        # Déduplique les communes présentes plusieurs fois en raison de multiples appellations ou lieux-dits
        awk -F ';' '{key = $1} !seen[key]++' - > download/codes_communes.csv

    echo "-----> Import des codes postaux..."
    cat 'download/codes_communes.csv' | psql -d $TEMP_DB -c "COPY codes_communes (code_insee, code_postal) FROM STDIN with (DELIMITER ';', FORMAT csv, HEADER);" 

    ###
    ###
    echo "--> Import de osm_roads..."

    echo "-----> Téléchargement du fichier OSM..."
    # Source: https://download.geofabrik.de/index.html
    pushd download
    wget -nc --show-progress $OSM_DATA_URL
    popd

    echo "-----> Décompression du fichier OSM..."
    mkdir -p download/osm-idf
    unzip -u -n -d download/osm-idf download/ile-de-france-latest-free.shp.zip

    echo "-----> Import de osm_roads..."
    ogr2ogr -f pgdump /vsistdout/ download/osm-idf/gis_osm_roads_free_1.shp -nln osm_roads --config PG_USE_COPY YES -lco GEOMETRY_NAME=geometry | psql -d $TEMP_DB

    ###
    ###
    echo "--> Calcul et export des intersections..."

    psql -d $TEMP_DB -tA -f ./junctions_json.sql > addok-data/junctions.json
}

cleanup
download
make_junctions_json
echo "Terminé"
