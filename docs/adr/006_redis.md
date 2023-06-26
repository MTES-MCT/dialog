# 006 - Ajout de Redis

* Date : 2023-06-26
* Personnes impliquées : Mathieu Marchois (auteur principal), Florimond Manca (relecture technique), équipe DiaLog (relecture et commentaires)
* Statut : ACCEPTÉ <!-- [BROUILLON|ACCEPTÉ|REJETÉ|DÉPRÉCIÉ] -->

## Contexte

Dans le cadre du projet DiaLog, nous avons mis en place une politique de déploiement continu, ce qui implique une mise en production automatique une fois une branche mergée sur main. Ce process peut être effectué plusieurs fois par jour.

Les utilisateurs de la plateforme utilisent des sessions pour pouvoir se connecter. Celles-ci sont gérées nativement par PHP. La conséquence de la mise en place de déploiement continu est que les utilisateurs se retrouvent déconnectés à chaque mise en production, car les conteneurs de Scalingo (et donc les sessions) sont détruits puis recréés.

## Décision

Nous utiliserons [Redis](https://redis.io/) pour le stockage des sessions utilisateurs.

## Options envisagées

### Option 1 - Redis

Site web : https://redis.io

**Description**

Redis est un système de stockage de données clé-valeur en mémoire, open source et rapide.

**Avantages**

* Les données résident en mémoire, ce qui permet un accès aux données à faible latence et à haut débit
* Haute disponibilité
* Optimisé pour le stockage des sessions
* Symfony a une [intégration native avec Redis](https://symfony.com/doc/current/session.html#store-sessions-in-a-key-value-database-redis) pour le stockage des sessions
* Pourra être utilisé dans d'autres contextes grâce à d'autres intégrations Symfony : [messagerie asynchrone](https://symfony.com/doc/current/messenger.html#redis-transport), [cache applicatif](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html)...

**Inconvénients**

* Ajout d'une technologie supplémentaire au projet

### Option 2 - PostgresSQL

Site web : https://www.postgresql.org/

**Description**

PostgreSQL est un système de gestion de base de données relationnelle qu'on utilise déjà sur DiaLog.

**Avantages**

* Technologie déjà utilisée sur DiaLog
* Symfony a une [intégration native avec PostgreSQL](https://symfony.com/doc/current/session.html#store-sessions-in-a-relational-database-mariadb-mysql-postgresql) pour le stockage des sessions
* Pourrait aussi être utilisé pour de la messagerie asynchrone performante via [l'intégration Symfony reposant sur PostgreSQL LISTEN/NOTIFY](https://symfony.com/doc/current/messenger.html#doctrine-transport)

**Inconvénients**

* Ne peut pas être utilisé pour du cache, contrairement à Redis
* Moins performant que Redis pour du stockage de session (cas d'usage clé-valeur) (voir [discussion SO (2012)](https://stackoverflow.com/questions/9153157/postgres-hstore-vs-redis-performance-wise))
