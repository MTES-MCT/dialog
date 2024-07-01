#!/bin/bash
set -euxo pipefail

SHPFILE=$1

./tools/shp2geojson.py $SHPFILE

# utf-8 fix
GEOJSONFILE=$(echo $SHPFILE | sed -e 's/.shp/.geojson/g' -)
sed -i -e 's/CH�TEAU/CHÂTEAU/g' $GEOJSONFILE > out.geojson

mv $GEOJSONFILE data/jop/zones.geojson
