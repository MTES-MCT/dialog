# BD TOPO

DiaLog intègre une partie de la [BD TOPO](https://geoservices.ign.fr/bdtopo) pour les besoins des calculs sur les localisations, tels que le géocodage de linéaires de voies.

## Fonctionnement général

Les [tables utilisées](#tables-utilisées) par DiaLog sont intégrées à la base de données de production (pour la production) et de staging (pour le développement et les environnements de branche).

La connexion appelée `bdtopo` est configurée par la variable d'environnement `BDTOPO_DATABASE_URL`.

## Utilisation

Pour le développement, récupérer la `BDTOPO_DATABASE_URL` de staging auprès d'un membre de l'équipe.

Dans le code PHP, les tables peuvent être requêtées directement en SQL avec la connexion Doctrine appelée `bdtopo` (voir `config/packages/doctrine.yaml`). Voir les exemples dans le code, ainsi que [Autowiring multiple Connections](https://symfony.com/bundles/DoctrineBundle/current/configuration.html#autowiring-multiple-connections) dans la documentation Symfony.

## Tables utilisées

Voir la liste `"tables"` dans [`tools/bdtopo_update.config.json`](../../tools/bdtopo_update.config.json).

Pour intégrer une nouvelle table (ou retirer une table qui n'est plus utilisée), mettre à jour `tools/bdtopo_update.config.json` puis suivre [mettre à jour les données](#mise-à-jour-des-données) sur staging et en production.

## Mise à jour des données

**Prérequis** : [Accès SSH aux DB Scalingo](./db.md#utiliser-une-db-scalingo-en-local)

La BD TOPO est mise à jour environ une fois par an.

Pour mettre à jour les données dans sur staging puis en production :

1. Télécharger la nouvelle version du [Thème Transports](https://geoservices.ign.fr/bdtopo#telechargementtransportter) pour la "France métropolitaine" ainsi que pour chaque DROM-COM (Guadeleoupe, etc) et les placer dans un même dossier, appelé ci-dessous `/path/to/bdtopo_transport`. (Environ 5 Go)
2. Lancer le script suivant :
    ```bash
    ./tools/bdtopo_update /path/to/bdtopo_transport dialog-staging
    ```
3. Une fois l'exécution réussie, vérifier le bon fonctionnement en se connectant à staging et en modifiant par exemple la voie nommée d'une localisation.
4. Lancer le script sur la production
    ```bash
    ./tools/bdtopo_update /path/to/theme_transport dialog
    ```

Le script `bdtopo_update` intègre à l'environnement indiqué les tables configurées dans `cotools/bdtopo_update.config.json`. Pour cela, il crée un schéma de base de données temporaire et y intègre les nouvelles tables, puis remplace les anciennes tables par les nouvelles.

## Références

* [ADR-008 - Utilisation de la BD TOPO](../adr/008_bdtopo.md)
