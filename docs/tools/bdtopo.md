# BD TOPO

DiaLog héberge une partie de la [BD TOPO](https://geoservices.ign.fr/bdtopo) pour les besoins des calculs sur les localisations, tels que le géocodage de linéaires de voies.

## Démarrage rapide

Pour le développement local, demandez la `BDTOPO_DATABASE_URL` à un membre de l'équipe et ajoutez-la à votre `.env.local` (créer ce fichier à la racine du projet si nécessaire).

Si vous cherchez à mettre en ligne une nouvelle version des tables BD TOPO, voir [Mettre à jour les données](#mettre-à-jour-les-données).

## Fonctionnement général

Les requêtes à la BD TOPO se font par une connexion dédiée, configurée dans la configuration doctrine (`config/packages/doctrine.yaml`).

L'URL de connexion utilisée par l'application est configurée par la variable d'environnement `BDTOPO_DATABASE_URL`.

Les variables `BDTOPO_2025_DATABASE_URL` et `BDTOPO_2025_2_DATABASE_URL` représentent quant à elles deux bases physiques entre lesquelles on peut basculer lors des mises à jour de données.

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

La BD TOPO EXPRESS est millésimée. Une nouvelle version sort **tous les 15 jours**. Le workflow GitHub Actions `bdtopo_update` s'exécute automatiquement tous les 15 jours (les 1er et 16 de chaque mois) et récupère/importe la dernière version disponible.

#### Fonctionnement de la mise à jour automatique (streaming S3)

En production (et dans la CI), la mise à jour **ne dézippe pas** l'archive localement (ce qui représenterait ~140 Go). À la place :

1. Les 6 parties de l'archive multi-volume `.7z.001` … `.7z.006` (~24 Go au total) sont téléchargées.
2. Ces parties étant un simple découpage binaire d'un unique conteneur `.7z`, elles sont **concaténées à la volée** et envoyées vers notre bucket S3 (Outscale) en un seul objet, via un upload multipart (`aws s3 cp -`), sans jamais matérialiser les ~24 Go sur le disque.
3. L'import lit ensuite **directement** chaque geopackage cible à l'intérieur de l'archive stockée sur S3, grâce aux systèmes de fichiers virtuels de GDAL `/vsi7z//vsis3/`. Aucune extraction locale n'est nécessaire.

La configuration S3 réutilise celle de Flysystem/Outscale (`config/packages/flysystem.yaml`). Les variables `S3_ACCESS_KEY`, `S3_SECRET_KEY`, `S3_BUCKET` et `S3_ENDPOINT` sont traduites en variables `AWS_*` attendues par GDAL (`/vsis3/`), avec l'adressage *path-style* d'Outscale (`AWS_VIRTUAL_HOSTING=FALSE`).


#### 🧪 Mise à jour en local (pour tester)

Cette section explique comment mettre à jour une base BDTOPO locale pour tester le processus avant de le faire en production.

**Prérequis** :
- Docker et Docker Compose en cours d'exécution
- Python 3 avec les modules `requests` et `beautifulsoup4` :
- Un outil d'extraction .7z :
  - **`7z` (p7zip)**
    - Sur macOS : `brew install p7zip`
    - Sur Linux (Debian/Ubuntu) : `sudo apt-get install p7zip-full`
    - Sur Linux (RedHat/CentOS) : `sudo yum install p7zip-plugins`
- PHP et Composer (pour les migrations d'index)

**Étapes** :

1. **Préparer la base de données locale** :

   ```bash
   # Démarrer Docker Compose si ce n'est pas déjà fait
   make start

   # Créer la base de données BDTOPO locale
   docker compose exec database createdb -U dialog dialog_bdtopo

   # Installer PostGIS dans la base
   docker compose exec database psql -U dialog -d dialog_bdtopo -c "CREATE EXTENSION IF NOT EXISTS postgis;"
   ```

2. **Télécharger, concaténer et importer les données** :

   ```bash
   ./tools/bdtopo_download_and_update \
     --local-archive \
     --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo \
     --overwrite \
     -y
   ```

   Le mode `--local-archive` télécharge les 6 parties de l'archive, les **concatène en un seul `.7z` dans `dump/`**, puis importe chaque geopackage **directement depuis l'archive** via GDAL `/vsi7z/`, sans dézipper les ~140 Go. C'est la méthode recommandée pour tester en local (pas de S3 requis).

   :information_source: **Note** : Le téléchargement peut prendre du temps (~40 Go compressé). Il faut prévoir l'espace disque pour l'archive concaténée dans `dump/`. Le script affiche une barre de progression.

   <details>
   <summary>Ancienne méthode : extraction complète sur disque</summary>

   Sans `--local-archive`, le script dézippe l'intégralité de l'archive (~130 Go décompressé) dans `dump/` avant l'import :

   ```bash
   ./tools/bdtopo_download_and_update \
     --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo \
     --overwrite \
     -y
   ```

   </details>

3. **Vérifier que les données ont été importées** :

   ```bash
   # Vérifier que les tables ont été créées
   docker-compose exec database psql -U dialog -d dialog_bdtopo -c "\dt"

   # Vérifier que les migrations ont été appliquées
   docker-compose exec database psql -U dialog -d dialog_bdtopo -c "SELECT * FROM doctrine_migration_versions;"
   ```

4. **Configurer l'application pour utiliser la base locale** :

   Ajoutez dans votre `.env.local` :

   ```bash
   # Pour l'application qui tourne dans Docker, utilisez le nom du service
   BDTOPO_DATABASE_URL=postgresql://dialog:dialog@database:5432/dialog_bdtopo
   ```

#### 🚀 Mise à jour en production

:information_source: La mise à jour charge la base physique choisie via `--prod=2025` ou `--prod=2025_2`, puis l'application peut être basculée en changeant `BDTOPO_DATABASE_URL` vers la base fraîchement chargée.

**Méthode recommandée : GitHub Actions**

Le workflow GitHub Actions `bdtopo_update` automatise tout le processus et est la méthode recommandée pour la production.

**Avantages** :
- Exécution automatisée (peut être planifiée)
- Pas besoin d'installer les dépendances localement
- Gestion automatique du tunnel vers la base de données Scalingo
- Logs et artefacts disponibles dans GitHub

**Utilisation** :

1. **Déclenchement manuel** :
   - Allez dans l'onglet "Actions" de GitHub
   - Sélectionnez le workflow "BD TOPO Update"
   - Cliquez sur "Run workflow"
   - Optionnellement, configurez les options :
     - `target` : Base BDTOPO cible (`2025` ou `2025_2`)
     - `skip_download` : Ignorer le téléchargement (réutiliser les fichiers existants)
     - `skip_import` : Uploader l'archive sur S3 sans lancer l'import
     - `keep_archives` : Conserver les parties .7z après l'upload S3

2. **Planification automatique** :
   - Le workflow est configuré pour s'exécuter automatiquement tous les 15 jours à 2h00 UTC
   - Vous pouvez modifier la planification dans `.github/workflows/bdtopo_update.yml` si nécessaire

3. **Vérification** :
   - Une fois l'exécution réussie, vérifiez le bon fonctionnement en vous connectant à staging et en modifiant par exemple la voie nommée d'une localisation
   - Les logs et artefacts sont disponibles dans l'onglet "Actions" de GitHub

**Prérequis GitHub Actions** :
- Les secrets `BDTOPO_2025_DATABASE_URL` et `BDTOPO_2025_2_DATABASE_URL` doivent être configurés dans les secrets GitHub Actions
- Les secrets S3 (Outscale) `S3_ACCESS_KEY`, `S3_SECRET_KEY`, `S3_BUCKET` et `S3_ENDPOINT` doivent être configurés
- Le secret `GH_SCALINGO_SSH_PRIVATE_KEY` doit être configuré (déjà fait pour les autres workflows)

**Méthode alternative : Script en local**

Si vous préférez exécuter le script depuis votre machine locale vers la production :

```bash
# S'assurer d'être authentifié sur Scalingo
scalingo login --ssh

# Renseigner la configuration S3 (Outscale) dans .env.local :
# S3_ACCESS_KEY, S3_SECRET_KEY, S3_BUCKET, S3_ENDPOINT

# Lancer la mise à jour en mode streaming S3
./tools/bdtopo_download_and_update \
  --s3 \
  --prod=2025 \
  --overwrite \
  -y
```

:information_source: **Note** : Le mode `--s3` évite l'extraction locale (~140 Go). Il faut néanmoins de l'espace disque pour télécharger les parties (~24 Go compressé) avant leur envoi en flux sur S3.

**Options du script** :
- `--download-dir DIR` : Répertoire où télécharger et dézipper
  - Défaut : `./dump` en local (évite les problèmes de RAM sur Linux où `/tmp` est monté en RAM)
  - Dans la CI GitHub Actions : `/tmp/bdtopo_download` (plus d'espace disponible)
- `--keep-archives` : Conserver les parties .7z après l'upload S3 / le dézippage
- `--skip-download` : Ignorer le téléchargement (utiliser fichiers existants)
- `--skip-import` : Ignorer l'import (uniquement télécharger, et uploader sur S3 en mode `--s3`)
- `--prod [2025|2025_2]` : Déployer vers l'environnement de production (`dialog-bdtopo-2025` ou `dialog-bdtopo-2025-2`)
- `--overwrite` : Réécrire les tables au lieu d'ajouter
- `--skip-migrate` : Ignorer l'exécution des migrations d'index après l'import
- `-y, --yes` : Accepter toutes les confirmations

**Options du mode streaming S3** :
- `--s3` : Activer le pipeline S3 (upload de l'archive concaténée + import en flux via `/vsi7z//vsis3/`, sans extraction locale)
- `--s3-bucket` : Bucket S3 cible (défaut : variable d'environnement `S3_BUCKET`)
- `--s3-key` : Clé de l'objet S3 (défaut : `bdtopo/bdtopo-latest.7z`)
- `--s3-endpoint` : Endpoint S3 (défaut : variable d'environnement `S3_ENDPOINT`)
- `--s3-region` : Région S3 (défaut : déduite de l'endpoint Outscale, ex. `cloudgouv-eu-west-1`)

**Option du mode archive locale** :
- `--local-archive` : Concaténer les parties en un seul `.7z` dans `dump/` et importer chaque geopackage directement via GDAL `/vsi7z/` (sans S3 ni extraction des ~140 Go). Recommandé pour tester en local.

**Note importante** :
Après un `--overwrite`, les migrations d'index sont **automatiquement exécutées** pour recréer tous les index (sauf si `--skip-migrate` est spécifié).

### Configurer des indexes

Les indexes sont gérés via des migrations dédiées à la BDTOPO (dossier `BdTopoMigrations`).

Pour créer une migration vide, utiliser `make bdtopo_migration`.

Pour tester une migration sur une BD TOPO locale, configurer `BDTOPO_DATABASE_URL` dans votre `.env.local` pour pointer sur la BD TOPO locale, puis utiliser `make bdtopo_migrate`.

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

### Scripts de mise à jour

#### `tools/bdtopo_download_and_update`

Script d'automatisation complet qui télécharge, dézippe et importe les données BDTOPO EXPRESS.

Ce script :
1. Récupère automatiquement les URLs de téléchargement depuis la page IGN
2. Télécharge toutes les parties de l'archive .7z
3. Dézippe les archives (gère les archives multi-volumes)
4. Appelle `bdtopo_update` pour importer les données

**Prérequis** :
- Python 3 avec `requests` et `beautifulsoup4` : `pip install requests beautifulsoup4`
- Un outil d'extraction .7z : `7z` (p7zip) ou `py7zr` (`pip install py7zr`)

**Utilisation** :

```bash
# Télécharger, dézipper et importer en local (utilise ./dump par défaut)
./tools/bdtopo_download_and_update --url postgresql://dialog:dialog@localhost:5432/dialog_bdtopo --overwrite -y

# Télécharger, dézipper et importer en production (utilise ./dump par défaut)
./tools/bdtopo_download_and_update --prod --overwrite -y

# Seulement télécharger et dézipper (sans importer)
./tools/bdtopo_download_and_update --skip-import

# Utiliser des URLs de téléchargement manuelles
./tools/bdtopo_download_and_update --urls https://data.geopf.fr/.../file.7z.001 https://data.geopf.fr/.../file.7z.002 ...
```

Voir `./tools/bdtopo_download_and_update --help` pour toutes les options.

#### `tools/bdtopo_update`

Script Python qui permet de déployer les tables de la BD TOPO sur une base PostgreSQL.

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
