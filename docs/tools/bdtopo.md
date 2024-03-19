# BD TOPO

DiaLog intègre une partie de la [BD TOPO](https://geoservices.ign.fr/bdtopo) pour les besoins des calculs sur les localisations, tels que le géocodage de linéaires de voies.

## Démarrage rapide

La variable d'environnement obligatoire `BDTOPO_DATABASE_URL` configure la connexion entre DiaLog et la base PostgreSQL qui contient les tables BD TOPO. (Cette connexion est différente de la connexion applicative habituelle pour des raisons de sécurité.)

Pour le développement local, vous pouvez utiliser la base de staging. Demandez la `BDTOPO_DATABASE_URL` correspondante à un membre de l'équipe.

Si vous cherchez à mettre en ligne une nouvelle version des tables BD TOPO, voir [Mettre à jour les données](#mettre-à-jour-les-données).

## Guides

### Faire des requêtes aux tables BD TOPO

Dans le code PHP / Symfony, vous pouvez effectuer des requêtes SQL aux tables de la BD TOPO en injectant la connexion à la BD TOPO :

```php
<?php
use Doctrine\DBAL\Connection;

class ExampleService {
    public function __construct(
        private readonly Connection $bdtopoConnection, // Attention à la casse : "bdtopo" et pas "bdTopo"
    ) {}

    public function findAllStreetsInCity(string $cityCode): array {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ST_AsGeoJSON(geometrie) AS geometry FROM voie_nommee WHERE code_insee = :code_insee',
            ['code_insee' => $cityCode],
        );

        // ...
    }
}
```

Pour information, cette connexion est configurée dans la configuration doctrine (`config/packages/doctrine.yaml`).

Pour plus d'informations, voir [Autowiring multiple Connections](https://symfony.com/bundles/DoctrineBundle/current/configuration.html#autowiring-multiple-connections) dans la documentation Symfony.

### Modifier les tables de la BD TOPO utilisées par DiaLog

Pour intégrer une nouvelle table, mettez à jour le fichier `tools/bdtopo_update.config.json` puis suivez le guide [Mettre à jour les données](#mettre-à-jour-les) pour ajouter la table à staging et à la production.

De même, pour retirer une table qui n'est plus utilisée, retirez-la du fichier de configuration puis mettez à jour les données sur staging et en production.

### Mettre à jour les données

**Prérequis** : [Accès SSH aux DB Scalingo](./db.md#utiliser-une-db-scalingo-en-local)

La BD TOPO est millésimée, c'est-à-dire qu'une nouvelle version sort environ une fois par an.

Pour mettre à jour les données dans sur staging et en production :

1. Télécharger la nouvelle version du [Thème Transports](https://geoservices.ign.fr/bdtopo#telechargementtransportter) pour la "France métropolitaine" ainsi que pour chaque DROM-COM (Guadeleoupe, etc), les décompresser et les placer dans un même dossier, appelé ci-dessous `~/path/to/bdtopo_transport`. (Environ 5 Go)
2. Lancer le script suivant :
    ```bash
    ./tools/bdtopo_update ~/path/to/bdtopo_transport dialog-staging
    ```
3. Une fois l'exécution réussie, vérifier le bon fonctionnement en se connectant à staging et en modifiant par exemple la voie nommée d'une localisation.
4. Lancer le script sur la production :
    ```bash
    ./tools/bdtopo_update ~/path/to/theme_transport dialog
    ```

L'exécution du script de mise à jour prendra typiquement plusieurs minutes (temps de transfert des données vers Scalingo, variable selon la qualité de la connexion).

### (Avancé) Utiliser une BD TOPO locale

Il est possible de télécharger la BD TOPO et de l'ingérer dans votre base de données PostgreSQL de développement.

Cette approche peut par exemple être utile pour tester une nouvelle version de la BD TOPO.

Pour cela, téléchargez les fichiers BD TOPO comme indiqué dans [Mettre à jour les données](#mettre-à-jour-les-données), puis lancez le script de mise à jour en pointant sur votre base locale :

```bash
./tools/bdtopo_update ~/path/to/bdtopo_transport postgresql://dialog:dialog@localhost:5432/dialog
```

## Référence

### Déploiements de la BD TOPO

On entend par "déploiement" une base de données PostgreSQL où sont ingérées les [tables BD TOPO utilisées par DiaLog](#liste-des-tables-bd-topo-utilisées-par-dialog).

| Déploiement | Dédié à | Utilisateur de connexion | `BDTOPO_DATABASE_URL` |
|---|---|---|---|
| production (dialog.beta.gouv.fr) | production | Utilisateur `dialog_bdtopo` en lecture seule créé sur Scalingo | Secret (configurés sur Scalingo) |
| staging (dialog.incubateur.net) | staging, dev local | _idem_ | Demander à un membre de l'équipe |

### Liste des tables BD TOPO utilisées par DiaLog

Voir la liste `"tables"` dans [`tools/bdtopo_update.config.json`](../../tools/bdtopo_update.config.json).

### Script `tools/bdtopo_update`

Ce script écrit en Python permet de déployer les tables de la BD TOPO sur une base PostgreSQL.

Fonctionnement : Ce script intègre à la base PostgreSQL cible les tables configurées dans le fichier de configuration `tools/bdtopo_update.config.json`. Pour cela, il ingère les données BD TOPO (format GeoPackage) à l'aide de `ogr2ogr` dans des tables temporaires, puis remplace les éventuelles anciennes tables par ces nouvelles.

Utilisation typique :

```bash
# Pour déployer les tables sur staging
./tools/bdtopo_update ~/path/to/bdtopo_transport dialog-staging

# Pour déployer les tables en prod
./tools/bdtopo_update ~/path/to/bdtopo_transport dialog --prod
```

Documentation :

```console
$ ./tools/bdtopo_update --help
usage: bdtopo_update [-h] [--prod] [-y] [-c CONFIG] transport_dir target

positional arguments:
  transport_dir         Path to directory of BD TOPO Transport Theme data
  target                Name of Scalingo app, or a PostgreSQL database URL

options:
  -h, --help            show this help message and exit
  --prod                Required if targetting 'dialog' app
  -y, --yes             Accept all prompts
  -c CONFIG, --config CONFIG
                        Path to config file. Default:
                        ./bdtopo_update.config.json
```

#### Configuration

Le fichier de configuration du script, par défaut `tools/bdtopo_update.config.json`, peut contenir ces paramètres :

* `tables` - Type `string[]` :
  * La liste des tables de la BD TOPO à intégrer. Les tables possibles sont référencées dans le document [Descrpitif de contenu BD TOPO](https://geoservices.ign.fr/documentation/donnees/vecteur/bdtopo).
* `custom_indexes` _(Optionnel)_ - Type `array` :
  * Une liste de définitions d'indexes personnalisés à configurer en fin de déploiement.
  * Définition d'un index :
    * `name` - Type `string` : le nom de l'index
    * `create` - Type `string` : la commande SQL à utiliser pour créer l'index. Utilisez `$name` pour faire référence au nom de l'index.

## Liens utiles

* [ADR-008 - Utilisation de la BD TOPO](../adr/008_bdtopo.md) - Trace historique de la décision de passer à l'intégration directe de la BD TOPO.
* [Site web BD TOPO](https://geoservices.ign.fr/bdtopo) - On peut y télécharger :
  * Les données : "[GeoPackage Thème transport](https://geoservices.ign.fr/bdtopo#telechargementtransportter)"
  * La [documentation](https://geoservices.ign.fr/documentation/donnees/vecteur/bdtopo)
* [ogr2ogr](https://gdal.org/programs/ogr2ogr.html) - Le programme utilisé pour intégrer les GeoPackages de la BD TOPO à PostgreSQL. Fait partie de la suite logicielle libre [GDAL](https://gdal.org/index.html). On utilise le [driver PostgreSQL / PostGIS](https://gdal.org/drivers/vector/pg.html).
