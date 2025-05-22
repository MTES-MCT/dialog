# BD TOPO

DiaLog héberge une partie de la [BD TOPO](https://geoservices.ign.fr/bdtopo) pour les besoins des calculs sur les localisations, tels que le géocodage de linéaires de voies.

## Démarrage rapide

Pour le développement local, demandez la `BDTOPO_2025_DATABASE_URL` à un membre de l'équipe et ajoutez-les à votre `.env.local` (créer ce fichier à la racine du projet si nécessaire).

Si vous cherchez à mettre en ligne une nouvelle version des tables BD TOPO, voir [Mettre à jour les données](#mettre-à-jour-les-données).

## Fonctionnement général

Les requêtes à la BD TOPO se font par une connexion dédiée, configurée dans la configuration doctrine (`config/packages/doctrine.yaml`).

L'URL de connexion est configurée par la variable d'environnement `BDTOPO_2025_DATABASE_URL`.

Cette connexion dispose de ses propres migrations, séparées des migrations applicatives de DiaLog. Cela permet de configurer des indexes, fonctions et autres objets PostgreSQL spécifiquement dédiés à l'optimisation des requêtes adressées aux tables BD TOPO.

## Guides

### Faire des requêtes aux tables BD TOPO

Dans le code PHP / Symfony, vous pouvez effectuer des requêtes SQL aux tables de la BD TOPO en injectant la connexion à la BD TOPO :

```php
<?php
use Doctrine\DBAL\Connection;

class ExampleService {
    public function __construct(
        private readonly Connection $bdtopo2025Connection, // Attention à la casse : "bdtopo" et pas "bdTopo"
    ) {}

    public function findAllStreetsInCity(string $cityCode): array {
        $rows = $this->bdtopo2025Connection->fetchAllAssociative(
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

La BD TOPO est millésimée. Une nouvelle version sort environ tous les 3 mois.

Pour mettre à jour les données, suivez ces étapes :

1. Téléchargez chaque partie du [BD TOPO Tous Thèmes France entière format GeoPackage WGS84](https://geoservices.ign.fr/bdtopo#telechargementgpkgfra) : ce sont des parties d'un fichier compressé avec 7Zip. Total compressé : 40 Go environ
2. Décompressez :
    1. Assurez-vous d'avoir 7zip installé : `sudo apt install p7zip-full`
    2. Placez les fichiers `.7z.001`, `.7z.002`, etc dans le même dossier
    3. Double-cliquez sur le fichier numéro 001 pour lancer la décompression.

    Total décompressé : 130 Go environ.

3. Testez sur une BD TOPO locale :

    1. Créez une base de données locale avec `docker-compose exec database createdb -U dialog dialog_bdtopo`
    2. Installez-y postgis : `make dbshell` puis `\c dialog_bdtopo` puis `create extension postgis;`
    3. Lancez le script suivant pour importer les données BD TOPO en local (prend plusieurs minutes) :

      ```bash
      ./tools/bdtopo_update ~/path/to/bdtopo --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo
      ```

    4. Modifiez votre .env.local avec `BDTOPO_2025_DATABASE_URL=postgresql://dialog:dialog@localhost:5432/dialog_bdtopo` puis naviguez sur DiaLog en local pour voir si le géocodage fonctionne comme prévu. Lancez aussi les tests d'intégration.

4. Une fois que tout semble OK, vous pouvez mettre à jour la BD TOPO de prod.

  :warning: **Attention** : le faire à une heure de faible trafic car cela prendra typiquement une heure voire plus. Pendant ce temps le géocodage sera indisponible, les utilisateurs peuvent rencontrer des plantages.

    ```bash
    ./tools/bdtopo_update ~/path/to/bdtopo --prod
    ```

5. Une fois l'exécution réussie, vérifiez le bon fonctionnement en se connectant à staging et en modifiant par exemple la voie nommée d'une localisation.

6. (Optionnel) Si des indexes doivent être ajoutés aux tables, suivez la section [Configurer des indexes](#configurer-des-indexes).

### Configurer des indexes

Les indexes sont gérés via des migrations dédiées à la BDTOPO (dossier `BdTopoMigrations`).

Pour créer une migration vide, utiliser `make bdtopo_migration`.

Pour tester une migration sur une BD TOPO locale, configurer  `BDTOPO_2025_DATABASE_URL` dans votre `.env.local` pour pointer sur la BD TOPO locale, puis utiliser `make bdtopo_migrate`.

Pour exécuter une migration en prod, faire une PR avec la migration et la merger. Un job GitHub Actions lancera la migration.

## Référence

### Déploiements de la BD TOPO

On entend par "déploiement" une base de données PostgreSQL où sont ingérées les [tables BD TOPO utilisées par DiaLog](#liste-des-tables-bd-topo-utilisées-par-dialog).

| App Scalingo | Utilisable par | URL de connexion |
|---|---|---|
| `dialog-bdtopo-2025` | Tous les environnements | Secret, demander à un membre de l'équipe |
| `dialog-bdtopo` | Tous les environnements | Secret, demander à un membre de l'équipe |

### Liste des tables BD TOPO utilisées par DiaLog

Voir la liste `"tables"` dans [`tools/bdtopo_update.config.json`](../../tools/bdtopo_update.config.json).

### Script `tools/bdtopo_update`

Ce script Python permet de déployer les tables de la BD TOPO sur une base PostgreSQL.

Fonctionnement : ce script intègre à la base PostgreSQL cible les tables configurées dans le fichier de configuration `tools/bdtopo_update.config.json`. Pour cela, il ingère les données BD TOPO (format GeoPackage) à l'aide de **`ogr2ogr`** dans des tables temporaires, puis remplace les éventuelles anciennes tables par ces nouvelles.

Par défaut le script pointe sur notre hébergement de la BD TOPO, mais peut aussi importer dans une base locale avec l'option `--url`.

Utilisation typique :

```bash
# Pour déployer les tables en production
./tools/bdtopo_update ~/path/to/bdtopo --prod

# Pour déployer les tables dans une base locale
./tools/bdtopo_update ~/path/to/bdtopo --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo
```

Documentation :

```console
$ ./tools/bdtopo_update --help
usage: bdtopo_update [-h] [--prod] [--url URL] [-y] [-c CONFIG] directory

positional arguments:
  directory             Path to directory containing BD TOPO data

options:
  -h, --help            show this help message and exit
  --prod                Confirm deployment to 'dialog-bdtopo-2025' app
  --url URL             Deploy to a PostgreSQL database identified by this
                        database URL
  -y, --yes             Accept all prompts
  -c CONFIG, --config CONFIG
                        Path to config file. Default:
                        ./bdtopo_update.config.json
```

#### Configuration

Le fichier de configuration du script, par défaut `tools/bdtopo_update.config.json`, peut contenir ces paramètres :

* `tables` - Type `{"name": string, "select_sql": string}[]` :
  * La liste des tables de la BD TOPO à intégrer. Les tables possibles sont référencées dans le document [Descrpitif de contenu BD TOPO](https://geoservices.ign.fr/documentation/donnees/vecteur/bdtopo). Le champ `select_sql` permet de ne sélectionner qu'une sous-partie des colonnes.

## Liens utiles

* [ADR-008 - Utilisation de la BD TOPO](../adr/008_bdtopo.md) - Trace historique de la décision de passer à l'intégration directe de la BD TOPO.
* [Site web BD TOPO](https://geoservices.ign.fr/bdtopo) - On peut y télécharger :
  * Les données : "[GeoPackage Thème transport](https://geoservices.ign.fr/bdtopo#telechargementtransportter)"
  * La [documentation](https://geoservices.ign.fr/documentation/donnees/vecteur/bdtopo)
* [ogr2ogr](https://gdal.org/programs/ogr2ogr.html) - Le programme utilisé pour intégrer les GeoPackages de la BD TOPO à PostgreSQL. Fait partie de la suite logicielle libre [GDAL](https://gdal.org/index.html). On utilise le [driver PostgreSQL / PostGIS](https://gdal.org/drivers/vector/pg.html).
