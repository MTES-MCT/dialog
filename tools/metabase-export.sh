#!/bin/bash
# Inspiré de : https://doc.incubateur.net/communaute/les-outils-de-la-communaute/autres-services/metabase/metabase#connecter-metabase-a-une-base-de-donnees-anonymisee
set -euxo pipefail

source .env.metabase ## Créé dans le workflow GitHub Actions

# Création des tables (persistera dans la DB source jusqu'à prochaine exécution de ce script)

psql $SRC_DATABASE_URL -c "DROP TABLE IF EXISTS analytics_user"
psql $SRC_DATABASE_URL -c "
CREATE TABLE analytics_user AS
SELECT
  uuid_generate_v4() AS id,
  u.registration_date
FROM \"user\" AS u"
psql $SRC_DATABASE_URL -c "ALTER TABLE analytics_user ADD PRIMARY KEY (id)"

# Création des index

# Copie vers la DB Metabase

pg_dump $SRC_DATABASE_URL -O -x -t analytics_user -c | psql "$DEST_METABASE_DATABASE_URL"
