#!/bin/sh -eu

# MIT License
# Florimond Manca, 2023
# Adapted from: https://github.com/BaseAdresseNationale/addok-docker

mkdir -p data/download

echo "---> Téléchargement des données BAN..."
cd data/download
wget -N -nv --show-progress https://adresse.data.gouv.fr/data/ban/adresses/latest/addok/addok-france-bundle.zip
cd ../../

echo "--> Décompression des données BAN..."
unzip -u -d ./data data/download/addok-france-bundle.zip
