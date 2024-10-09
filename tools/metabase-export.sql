-- metabase-export.sql
-- Ce script est conçu pour être exécuté sur la base de données PostgreSQL de l'instance Metabase (destination).
-- Il consiste à extraire des données de la base applicative (source) pour les charger dans des tables Metabase.

-- Configuration générale
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- CONNEXION À LA DB APPLICATIVE (source)
-- Voir : https://www.postgresql.org/docs/current/contrib-dblink-connect.html
CREATE EXTENSION IF NOT EXISTS dblink;
SELECT dblink_connect('src', current_setting('custom.src_database_url'));

-- COLLECTE DES DONNÉES D'INDICATEURS

-- # Utilisateurs actifs
-- À chaque exécution, on ajoute la liste des dates de dernière activité pour chaque utilisateur, assortie de la date d'exécution.
-- Dans Metabase cela permet de calculer le nombre d'utilisateurs actif au moment de chaque exécution.
-- (Par exemple avec un filtre : "[last_active_at] >= [uploaded_at] - 7 jours", puis en groupant sur le uploaded_at.)
CREATE TABLE IF NOT EXISTS analytics_user_active (id UUID NOT NULL, uploaded_at TIMESTAMP(0), last_active_at TIMESTAMP(0), PRIMARY KEY(id));
CREATE INDEX IF NOT EXISTS idx_analytics_user_active_uploaded_at ON analytics_user_active (uploaded_at);

WITH params AS (
    -- Calculé 1 bonne fois pour toute pour que toutes les lignes utilisent exactement la même valeur à des fins de groupement dans Metabase
    SELECT NOW() as current_date
)
INSERT INTO analytics_user_active(id, uploaded_at, last_active_at)
SELECT uuid_generate_v4() AS id, p.current_date AS uploaded_at, u.last_active_at AS last_active_at
FROM
    dblink('src', 'SELECT last_active_at FROM "user"') AS u(last_active_at TIMESTAMP(0) WITH TIME ZONE),
    params AS p
;
