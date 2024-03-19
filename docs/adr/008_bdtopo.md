# 008 - Utilisation de la BD TOPO

* Création : 2024-03-12
* Personnes impliquées : Florimond Manca (auteur principal), Mathieu Marchois, équipe DiaLog
* Statut : Accepté

## Contexte

DiaLog utilise diverses sources de données pour le géocodage.

Actuellement, il s'agit de :

* [L'API Adresse](https://adresse.data.gouv.fr/api-doc), pour le géocodage des adresses (exemple : "3 Rue Donnée, Ville Untelle") ;
* [L'API WFS de l'IGN](https://geoservices.ign.fr/documentation/services/api-et-services-ogc/donnees-vecteur-wfs-ogc), pour l'obtention du linéaire des voies nommées entières (exemple : "Rue Donnée" dans la ville dont le code Insee est 01234).

Il a été jugé que la qualité de service de l'API de l'IGN est insufissante pour les besoins de DiaLog, notamment au point de vue temps de réponse et fiabilité. Bien que des mesures précises n'aient pas été effectuée, il a été constaté des requêtes prenant presque toujours au-delà de 500 ms et régulièrement plusieurs secondes. Cela occasionne des lenteurs notables dans l'application et dégrade l'expérience utilisateur. L'API de l'IGN a aussi pu être partiellement voire totalement indisponible de façon intermittente (temps de réponse démesurés, erreurs serveur occasionnelles), peut-être en lien avec la migration vers la Géoplateforme.

Même dans le cas où les comportements extrêmes possiblement liés à la migration Géoplateforme s'amélioraient, les temps de réponse élevés et les lenteurs occasionnées rendaient pertinents l'étude d'une solution alternative.

## Décision

Les tables de la BD TOPO nécessaires à DiaLog seront intégrées à la base de production et à la base de staging, selon l'approche détaillée dans l'option 2.

Conséquences :

* Un utilisateur `dialog_bdtopo` sera créé en prod et sur staging avec accès "read-only" à la base de données.
* La connexion entre DiaLog et les données BD TOPO se feront par une connexion PostgreSQL utilisant l'utilisateur `dialog_bdtopo`.
* La base de staging sera [ouverte à Internet](https://doc.scalingo.com/platform/databases/access#internet-accessibility) pour permettre l'accès à ses tables BD TOPO en développement sans avoir besoin d'outillage Scalingo.
* Un script sera réalisé pour l'ingestion des tables souhaitées de la BD TOPO (création initiale ou mise à jour). Ce script intègrera la création des indexes pertinents.
* Un espace de stockage suffisant devra être prévu dans la base de données de production : au moins 2 x 5 Go = 10 Go rien que pour la BD TOPO (la mise à jour "tout ou rien" implique l'existence temporaire des données en double). Le cas échéant, le plan Scalingo devra être augmenté.
* De la documentation sera ajoutée pour le fonctionnement de l'intégration BD TOPO et la mise à jour des données.

## Options envisagées

### Option 1 - Ne rien faire

Avantages

* Pas de travail supplémentaire

Inconvénients

* Les lenteurs et perturbations persistent, impactant à la fois l'expérience utilisateur et la productivité lors du développement.
* Divers cas d'erreurs à gérer : ruptures réseau, timeouts, erreurs HTTP inattendues, changement de format...

### Option 2 - Hébergement partiel de la BD TOPO

Cette option consisterait à intégrer directement dans la base de données DiaLog les tables de la [BD TOPO](https://geoservices.ign.fr/bdtopo#telechargementtransportter) utilisées par DiaLog pour les calculs nécessitant des données BD TOPO, tels que les linéaires de voies ou de routes.

Avantages

* Maîtrise complète des données
* Permet d'atteindre des temps de réponse inférieurs à 100 ms et de façon beaucoup plus fiable
* Permet l'optimisation des requêtes faites spécifiquement par DiaLog, notamment par la création d'indexes (impossible avec l'API)
* Moins de cas d'erreurs possibles

Inconvénients

* Coût opérationnel pour la gestion des tables de la BD TOPO : hébergement (prod, staging), mise à jour (~ annuelle), utilisation en développement, documentation...
* Coût financier d'hébergement : passage de la DB staging du plan Sandbox à Starter 512M, soit +7€/mois.

### Approche détaillée

#### Mise en place

Il s'agirait de **télécharger le thème "Transports" de la BD TOPO** (environ 4.5 Go) et d'**ingérer dans la base de données de production les tables qui nous intéressent** telles que `voie_nommee` (1.8 Go), `route_numerotee_ou_nommee` (400 Mo) ou encore `troncon_de_route`.

Les tables sont fournies au format GeoPackage et peuvent être ingérées avec l'outil [**`ogr2ogr`**](https://gdal.org/programs/ogr2ogr.html) fourni par la librairie de référence [GDAL](https://gdal.org/index.html), laquelle [supporte PostgreSQL / PostGIS](https://gdal.org/drivers/vector/pg.html#driver-capabilities) :

```bash
ogr2ogr -f PostgreSQL "PG:postgresql://dialog_bdtopo:password@localhost:5432/dialog" /path/to/voie_nommee.gpkg
```

Pour assurer une **portabilité** maximum parmi l'équipe de développement (Linux, Windows...), on pourra envisager l'utilisation d'ogr2ogr via l'[image Docker de GDAL](https://github.com/OSGeo/gdal/pkgs/container/gdal). Des binaires Windows et Debian sont aussi disponibles.

Suite à une ingestion, les **indexes** judicieux seront créés pour accélérer l'exécution des requêtes.

#### Hébergement

Les tables de la BD TOPO seraient hébergées sur deux instances :

* Dans la base de production : pour la production ;
* Dans la base de staging : pour l'environnement de staging, les environnements de branche, et le développement local.

Cette séparation évite l'accès aux données de production depuis un poste local qui, du fait les limitations de la gestion des droits PostgreSQL sur Scalingo (voir [Sécurisation des accès](#sécurisation-des-accès)), serait possible si on utilisait uniquement la base de production comme hôte des données BD TOPO.

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

En pratique, l'hébergement Scalingo impose quelques limitations, notamment le fait de n'avoir accès qu'à une seule `DATABASE` sans pouvoir en créer d'autres, ainsi qu'une gestion limitée des droits utilisateurs via l'interface web (création (avec option "lecture seule"), suppression).

La proposition est donc d'**héberger les tables de la BD TOPO au même endroit que les données applicatives**, à savoir dans la `DATABASE` fournie par Scalingo et sous le schéma `public`.

Pour cela, un utilisateur BD TOPO "read-only" sera créé via l'interface web Scalingo pour la connexion entre DiaLog et les tables de la BD TOPO.

Ces identifiants BD TOPO doivent être considérés comme tout aussi sensibles que pour la base de données de production. En effet, cette approche permettrait quand même, en cas de fuite des identifiants de l'utilisateur BD TOPO, d'avoir accès en lecture seule à l'ensemble des données, en particulier les données utilisateur (nom, prénom, adresse mail) de la table `user`. Cela justifie également l'hébergement double (en production d'une part, sur staging d'autre part) indiqué dans [Hébergement](#hébergement) pour ne pas élargir la surface d'accès aux données de production.

La protection de l'URL BD TOPO est toute aussi importante sur staging car la base devra être [ouverte à Internet](https://doc.scalingo.com/platform/databases/access#internet-accessibility) pour y accéder en local, ce qui retirera la couche de sécurisation SSH de Scalingo.

#### Mise à jour

Une mise à jour semi-manuelle (déclenchement manuel à l'aide de scripts) est envisageable puisque la BD TOPO est mise à jour peu fréquemment (une publication par an environ).

La mise à jour des données BD TOPO pourra se faire par l'équipe de développement comme suit :

* Télécharger en local la nouvelle version du thème Transports
* Exécuter un script utilitaire qui fera les opérations suivantes :
  * Importer les données avec `ogr2ogr` dans des tables temporaires
  * Une fois l'import entièrement réussi, remplacer les tables précédentes par les nouvelles tables
  * Faire en sorte que le processus soit "atomique" : soit la mise à jour réussit entièrement, soit rien ne change

Cette approche minimise les risques de rupture de service, comparativement à la suppression des tables préalable à leur ingestion. En effet, l'ingestion des tables pourrait prendre plusieurs dizaines de secondes, alors qu'un renommage final sera très rapide. Néanmoins, elle implique une petite complexité supplémentaire pour le renommage, et nécessite de stocker temporairement dans PostgreSQL l'ancienne version ET la nouvelle version des données, ce qui implique un surdimensionnement du stockage de la base par rapport aux besoins au runtime.

### Réversibilité

Si la qualité du service de l'API WFS de l'IGN s'améliore au point que le surcoût opérationnel (modeste mais non-nul) de gestion de notre hébergement BD TOPO n'est plus justifié, il sera toujours possible de récupérer le code de l'ancien géocodeur basé sur l'API WFS.

Les données étant les mêmes puisque toutes deux issues de la BD TOPO, on ne devrait pas observer d'incohérences.

## Références

* [BD TOPO](https://geoservices.ign.fr/bdtopo) (documentation, téléchargements)
* [GDAL](https://gdal.org/index.html), [Driver PostgreSQL / PostGIS pour GDAL](https://gdal.org/drivers/vector/pg.html), [ogr2ogr](https://gdal.org/programs/ogr2ogr.html)
