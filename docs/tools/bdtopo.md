# BD TOPO

DiaLog héberge une partie de la [BD TOPO](https://geoservices.ign.fr/bdtopo) pour les besoins des calculs sur les localisations, tels que le géocodage de linéaires de voies.

## Démarrage rapide

Pour le développement local, demandez la `BDTOPO_DATABASE_URL` à un membre de l'équipe et ajoutez-là à votre `.env.local` (créer ce fichier à la racine du projet si nécessaire).

Si vous cherchez à mettre en ligne une nouvelle version des tables BD TOPO, voir [Mettre à jour les données](#mettre-à-jour-les-données).

## Fonctionnement général

Les requêtes à la BD TOPO se font par une connexion dédiée, configurée dans la configuration doctrine (`config/packages/doctrine.yaml`).

L'URL de connexion est configurée par la variable d'environnement `BDTOPO_DATABASE_URL`.

Cette connexion dispose de ses propres migrations, séparées des migrations applicatives de DiaLog. Cela permet de [configurer des indexes], fonctions et autres objets PostgreSQL spécifiquement dédiés à l'optimisation des requêtes adressées aux tables BD TOPO.

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

### Modifier les tables de la BD TOPO utilisées par DiaLog

Pour intégrer une nouvelle table, mettez à jour le fichier `tools/bdtopo_update.config.json` puis suivez le guide [Mettre à jour les données](#mettre-à-jour-les-données) pour ajouter la table à notre hébergement de la BD TOPO.

De même, pour retirer une table qui n'est plus utilisée, retirez-la du fichier de configuration puis mettez à jour les données.

### Intégrer de nouvelles colonnes dans la BD TOPO

Pour les plus grosses tables comme `troncon_de_route`, on n'intègre que les colonnes dont on a besoin afin de limiter la taille de la BD TOPO sur le disque.

Si vous avez besoin d'intégrer une nouvelle colonne, mettez à jour `tools/bdtopo_update.config.json`, puis [mettez à jour les données](#mettre-à-jour-les-données) mais ajoutez l'option `--overwrite` lors de l'exécution du script de mise à jour :

```bash
./tools/bdtopo_update ~/path/to/bdtopo --prod --overwrite
```

Cette option va supprimer les tables et refaire un import de zéro. Ensuite elle réexécute les migrations BD TOPO afin de recréer les indexes.

Cet import de zéro est nécessaire car sinon la nouvelle colonne sera ignorée, seul le contenu des colonnes existantes sera mis à jour.

### Configurer des indexes

La création d'indexes judicieux sur les tables BD TOPO peut permettre d'accélérer les requêtes.

Pour configurer des indexes, créez une migration qui s'appliquera à notre instance BD TOPO :

```bash
make bdtopo_migration
```

Le fichier de migration doit être ajouté à `main` via une PR, comme n'importe quel autre code.

Une fois la PR mergée, les migrations seront exécutées par GitHub Actions grâce au workflow `bdtopo_migrate`.

### Mettre à jour les données

**Prérequis** : [Accès SSH aux DB Scalingo](./db.md#utiliser-une-db-scalingo-en-local)

La BD TOPO est millésimée, c'est-à-dire qu'une nouvelle version sort environ une fois par an.

Pour mettre à jour les données, suivez ces étapes :

1. Téléchargez chaque partie du [Thème transport par territoire format Geopackage projection légale](https://geoservices.ign.fr/bdtopo#telechargementtransportter) 
    * 1 fichier zip "France Métropolitaine"
    * 1 fichier zip par DROM-COM (Guadeleoupe, etc),

2. Décompressez l'entièreté de chaque fichier zip et regroupez-les dans un même dossier, appelé ci-dessous `~/path/to/bdtopo`.

    Exemple de structure du dossier :

    ```console
    $ tree ~/path/to/bdtopo
    /home/user/path/to/bdtopo
    ├── BDTOPO_3-3_TRANSPORT_GPKG_LAMB93_FXX_2023-12-15
    │   └── BDTOPO
    │       └── ...
    ├── BDTOPO_3-3_TRANSPORT_GPKG_RGAF09UTM20_BLM_2023-12-15
    │   └── BDTOPO
    │       └── ...
    └── ...
    ```

3. Lancez le script suivant :

    ```bash
    ./tools/bdtopo_update ~/path/to/bdtopo --prod
    ```

    Ce script va mettre à jour le contenu de notre hébergement BD TOPO à partir des fichiers locaux.

    L'exécution prend typiquement plusieurs minutes (temps de transfert des données vers Scalingo, variable selon la qualité de la connexion).

4. Une fois l'exécution réussie, vérifiez le bon fonctionnement en se connectant à staging et en modifiant par exemple la voie nommée d'une localisation.

5. (Optionnel) Si des indexes doivent être ajoutés aux tables, suivez la section [Configurer des indexes](#configurer-des-indexes).

### (Avancé) Utiliser une BD TOPO locale

Il est possible de télécharger la BD TOPO et de l'ingérer dans une base de données PostgreSQL locale.

Cela peut être utile pour tester une nouvelle version de la BD TOPO par exemple.

Pour cela, téléchargez les fichiers BD TOPO comme indiqué dans [Mettre à jour les données](#mettre-à-jour-les-données), puis lancez le script de mise à jour en pointant sur votre base locale :

```bash
./tools/bdtopo_update ~/path/to/bdtopo --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo
```

## Référence

### Déploiements de la BD TOPO

On entend par "déploiement" une base de données PostgreSQL où sont ingérées les [tables BD TOPO utilisées par DiaLog](#liste-des-tables-bd-topo-utilisées-par-dialog).

| App Scalingo | Utilisateur | Utilisable par | URL de connexion |
|---|---|---|---|
| `dialog-bdtopo` | `dialog_app` (utilisateur avec accès en lecture seule créé sur Scalingo) | Tous les environnements | Secret, demander à un membre de l'équipe |

### Liste des tables BD TOPO utilisées par DiaLog

Voir la liste `"tables"` dans [`tools/bdtopo_update.config.json`](../../tools/bdtopo_update.config.json).

### Script `tools/bdtopo_update`

Ce script Python permet de déployer les tables de la BD TOPO sur une base PostgreSQL.

Fonctionnement : ce script intègre à la base PostgreSQL cible les tables configurées dans le fichier de configuration `tools/bdtopo_update.config.json`. Pour cela, il ingère les données BD TOPO (format GeoPackage) à l'aide de `ogr2ogr` dans des tables temporaires, puis remplace les éventuelles anciennes tables par ces nouvelles.

Par défaut le script pointe sur notre hébergement de la BD TOPO, mais peut aussi importer dans une base locale avec l'option `--url`.

Utilisation typique :

```bash
# Pour déployer les tables en production (dialog-bdtopo)
./tools/bdtopo_update ~/path/to/bdtopo_transport --prod

# Pour déployer les tables dans une base locale
./tools/bdtopo_update ~/path/to/bdtopo_transport --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo
```

Documentation :

```console
$ ./tools/bdtopo_update --help
usage: bdtopo_update [-h] [--prod] [--url URL] [-y] [-c CONFIG] directory

positional arguments:
  directory             Path to directory containing BD TOPO data

options:
  -h, --help            show this help message and exit
  --prod                Confirm deployment to 'dialog-bdtopo' app
  --url URL             Deploy to a PostgreSQL database identified by this
                        database URL
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
