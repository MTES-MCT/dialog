# 008 - Utilisation de la BD TOPO

* Création : 2024-03-12
* Personnes impliquées : Florimond Manca (auteur principal), Mathieu Marchois, équipe DiaLog
* Statut : Brouillon

## Contexte

DiaLog utilise diverses sources de données pour le géocodage.

Actuellement, il s'agit de :

* [L'API Adresse](https://adresse.data.gouv.fr/api-doc), pour le géocodage des adresses (exemple : "3 Rue Donnée, Ville Untelle") ;
* [L'API WFS de l'IGN](https://geoservices.ign.fr/documentation/services/api-et-services-ogc/donnees-vecteur-wfs-ogc), pour l'obtention du linéaire des voies nommées entières (exemple : "Rue Donnée" dans la ville dont le code Insee est 01234).

Il a été jugé que la qualité de service de l'API de l'IGN est insufissante pour les besoins de DiaLog, notamment au point de vue temps de réponse et fiabilité. Bien que des mesures précises n'aient pas été effectuée, il a été constaté des requêtes prenant presque toujours au-delà de 500 ms et régulièrement plusieurs secondes. Cela occasionne des lenteurs notables dans l'application et dégrade l'expérience utilisateur. L'API de l'IGN a aussi pu être partiellement voire totalement indisponible de façon intermittente (temps de réponse démesurés, erreurs serveur occasionnelles), peut-être en lien avec la migration vers la Géoplateforme.

Même dans le cas où les comportements extrêmes possiblement liés à la migration Géoplateforme s'amélioraient, les temps de réponse élevés et les lenteurs occasionnées rendaient pertinents l'étude d'une solution alternative.

## Décision - _(Proposition)_

Les tables de la BD TOPO nécessaires à DiaLog seront intégrées à la base de production, selon l'approche détaillée dans l'option 2.

Conséquences :

* Un utilisateur `dialog_bdtopo` sera créé avec accès "read-only" à la base de données.
* La connexion entre DiaLog et les données BD TOPO se feront par une connexion PostgreSQL utilisant l'utilisateur `dialog_bdtopo`.
* Un script sera réalisé pour l'ingestion des tables souhaitées de la BD TOPO (création initiale ou mise à jour). Ce script intègrera la création des indexes pertinents.
* Un espace de stockage suffisant devra être prévu dans la base de données de production : au moins 10 Go. Le cas échéant, le plan Scalingo devra être augmenté.
* De la documentation sera ajoutée pour le fonctionnement de l'intégration BD TOPO et la mise à jour des données.

## Options envisagées

### Option 1 - Ne rien faire

Avantages

* Pas de travail supplémentaire

Inconvénients

* Les lenteurs et perturbations persistent, impactant à la fois l'expérience utilisateur et la productivité lors du développement.

### Option 2 - Interfaçage direct avec la BD TOPO

Cette option consisterait à intégrer directement la [BD TOPO](https://geoservices.ign.fr/bdtopo#telechargementtransportter), dont l'API WFS fournit essentiellement une interface HTTP, dans la base de données DiaLog, afin de réaliser les .

### Approche détaillée

#### Mise en place

Il s'agirait de **télécharger le thème "Transports" de la BD TOPO** (environ 4.5 Go) et d'**ingérer dans la base de données de production les tables qui nous intéressent** telles que `voie_nommee` (1.8 Go), `route_numerotee_ou_nommee` (400 Mo) ou encore `troncon_de_route`.

Les tables sont fournies au format GeoPackage et peuvent être ingérées avec l'outil [**`ogr2ogr`**](https://gdal.org/programs/ogr2ogr.html) fourni par la librairie de référence [GDAL](https://gdal.org/index.html) :

```bash
ogr2ogr -f PostgreSQL "PG:user=dialog password=dialog host=localhost port=5432 dbname=dialog_bdtopo" /path/to/voie_nommee.gpkg
```

Pour assurer une **portabilité** maximum parmi l'équipe de développement (Linux, Windows...), on pourra envisager l'utilisation des commandes GDAL via son [image Docker](https://github.com/OSGeo/gdal/pkgs/container/gdal). Sinon, des binaires Windows et Debian sont aussi disponibles.

Suite à une ingestion, les **indexes** judicieux seront créés pour accélérer l'exécution des requêtes.

#### Hébergement

Dans un premier temps, il est supposé que la charge devrait pouvoir être absorbée par une seule instance.

Les tables BD TOPO seraient donc intégrées à la base de données de production pour requêtage par tous les environnements : production, développement local, staging, environnements de branche...

#### Performance attendue

Des premiers tests via la [PR #677](https://github.com/MTES-MCT/dialog/pull/677) suggèrent les résultats suivants :

| Métrique | Avant | Après | Évolution |
|---|---|---|---|
| Temps de réponse, latence comprise (min, typique, max) | ~500ms, ~ 1-2s, > 10s (estimations) | Avec indexes : ~20ms, ~ 100ms, < 200 ms (estimations) ; Sans indexes : ~300ms, ~1s, < 2s (estimations) | > 20x plus rapide, moindre variabilité |
| Disponibilité (timeouts compris) | < 90% (estimation) | > 98% (garanti par le [SLA Scalingo](https://scalingo.com/service-level-agreement)) | Meilleure disponibilité |

D'une part le requêtage direct à PostgreSQL permet de bénéficier de l'excellent performance de ce SGBD, notamment combiné à des indexes conçus judicieusement (ce que l'API IGN ne permet pas de faire).

D'autre part, nous bénéficions par extension de la qualité de service de l'hébergeur Scalingo, au même niveau que pour la base de données de production puisque la BD TOPO y sera hébergée.

#### Transformation des requêtes API WFS en requêtes aux tables BD TOPO

Les requêtes actuellement réalisées à l'API WFS peuvent être facilement traduites en SQL adressé aux ables BD TOPO.

Par exemple, cette requête [GetFeature](https://docs.geoserver.org/stable/en/user/services/wfs/reference.html#wfs-getfeature) permettant d'interroger la table `voie_nommee` ...

```http
GET https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&Version=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=code_insee='01234' HTTP/1.1
```

... correspondra à une requête SQL de ce type :

```sql
SELECT * FROM voie_nommee WHERE code_insee = '01234';
```

Ces requêtes pourront être réalisées avec l'infrastructure existante, à savoir Symfony avec l'ORM Doctrine. Ce dernier permet notamment de faire des requêtes SQL directement et de configurer plusieurs connexions de base de données (nécessaire pour la configuration d'identifiants différents pour les données applicatives et les données BD TOPO, voir [Sécurisation des accès](#sécurisation-des-accès)).

#### Sécurisation des accès

Idéalement, les données BD TOPO seraient hébergées dans une base de données indépendante de la base de données applicative, et configurée avec un utilisateur en lecture seule.

En pratique, l'hébergement Scalingo impose quelques limitations, notamment le fait de n'avoir accès qu'à une seule `DATABASE` sans pouvoir en créer d'autres, ainsi qu'une gestion limitée des droits utilisateurs via l'interface web (création avec option "lecture seule", suppression).

La proposition est donc d'**héberger les tables de la BD TOPO au même endroit que les données applications**, à savoir dans la `DATABASE` fournie par Scalingo et sous le schéma `public`.

Pour cela, un utilisateur BD TOPO "read-only" sera créé via l'interface web Scalingo pour la connexion entre DiaLog et les tables de la BD TOPO.

Remarques :

* Cette approche permettrait, en cas de fuite des identifiants de l'utilisateur BD TOPO, d'avoir accès en lecture seule aux données personnelles des utilisateurs (nom et prénom, adresse email) via la table `user`, même si les mots de passe restent inaccessibles car hachés. Cette réserve est jugée acceptable par compromis avec la complexité opérationnelle d'une séparation complète via Scalingo. Cependant, **les identifiants à la BD TOPO devront être considérés tout aussi sensibles**. Un accès public est à proscrire.
* Une autre option aurait été de migrer les données de DiaLog dans un autre schéma que `public`, afin de n'y garder que la BD TOPO et d'exclure l'utilisateur BD TOPO de l'accès aux données applicatives. Cette option est écartée en raison du trop grand acroissement de complexité de gestion de la base de données, notamment en matière de gestion des droits.

#### Mise à jour

Une mise à jour semi-manuelle (déclenchement manuel à l'aide de scripts) est envisageable puisque la BD TOPO est mise à jour peu fréquemment (une publication par an environ).

La mise à jour des données BD TOPO pourra se faire par l'équipe de développement comme suit :

* Télécharger en local la nouvelle version du thème Transports ;
* Créer un schéma temporaire dans la base de données hébergée sur Scalingo, par exemple `bdtopo_migration` ;
* Y intégrer les données avec `ogr2ogr` en spécifiant le schéma `bdtopo_migration`.
* Renommer temporairement les tables dans le schéma `public`, puis déplacer les nouvelles tables de `bdtopo_migration` vers `public`.
* Supprimer les anciennes tables du schéma `public`.

Cette approche minimise les risques de rupture de service, comparativement à la suppression des tables préalable à leur ingestion. En effet, l'ingestion des tables pourrait prendre plusieurs dizaines de secondes, alors qu'un renommage final sera très rapide.

Un script utilitaire permettra de faciliter l'exécution par l'équipe de développement.

## Références

* [BD TOPO](https://geoservices.ign.fr/bdtopo) (documentation, téléchargements)
* [GDAL](https://gdal.org/index.html) et [ogr2ogr](https://gdal.org/programs/ogr2ogr.html)
