#!/bin/bash
# Inspiré de : https://doc.incubateur.net/communaute/les-outils-de-la-communaute/autres-services/metabase/metabase#connecter-metabase-a-une-base-de-donnees-anonymisee
set -euxo pipefail

# URL de la DB DiaLog du point de vue de la DB Metabase (sera utilisé avec dblink) 
SRC_DATABASE_URL=$1

# URL de la DB Metabase du point de vue de ce script
DEST_DATABASE_URL=$2

export PGOPTIONS="-c custom.src_database_url=${SRC_DATABASE_URL}"

# ON_ERROR_STOP=1 s'assure que cette commande échoue (return code != 0) si le script SQL a des statements qui échouent.
# (Par défaut avec -f on a toujours un return code 0 et un statement en échec n'empêche pas les suivants de s'exécuter.)
# https://engineering.nordeus.com/psql-exit-on-first-error/
psql $DEST_DATABASE_URL -v ON_ERROR_STOP=1 -f ./tools/metabase-export.sql
