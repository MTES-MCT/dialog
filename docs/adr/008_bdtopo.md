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

Les tables de la BD TOPO nécessaires à DiaLog seront intégrées dans une base de données déployée dans une nouvelle application Scalingo, selon l'approche détaillée dans l'option 2.

Conséquences :

* Une application `dialog-bdtopo` sera créée sur Scalingo avec un add-on PostgreSQL sous le plan Starter 1G (1 GB RAM, 20 GB Disque).
* Cette base sera utilisée par tous les environnements (production, staging, branches, local...).
* Un utilisateur `dialog_app` avec accès "read-only" y sera créé. Il sera utilisé pour la connexion PostgreSQL entre DiaLog et notre hébergement de la BD TOPO. L'utilisateur principal créé par Scalingo sera conservé et servira à l'administration (mise à jour des données, etc).
* La base sera [ouverte à Internet](https://doc.scalingo.com/platform/databases/access#internet-accessibility) pour permettre l'accès en développement sans avoir besoin d'outillage Scalingo.
* Un script sera réalisé pour l'ingestion des tables souhaitées de la BD TOPO (création initiale ou mise à jour). Ce script permettra de configurer les indexes pertinents.
* De la documentation sera créée pour le fonctionnement de l'intégration BD TOPO et la mise à jour des données.

## Options envisagées

### Option 1 - Ne rien faire

Avantages

* Pas de travail supplémentaire

Inconvénients

* Les lenteurs et perturbations persistent, impactant à la fois l'expérience utilisateur et la productivité lors du développement.
* Divers cas d'erreurs à gérer : ruptures réseau, timeouts, erreurs HTTP inattendues, changement de format...

### Option 2 - Hébergement de certaines tables de la BD TOPO

Cette option consisterait à héberger nous-mêmes une instance PostgreSQL contenant les tables de la [BD TOPO](https://geoservices.ign.fr/bdtopo#telechargementtransportter) utilisées par DiaLog.

Avantages

* Maîtrise complète des données
* Permet d'atteindre des temps de réponse inférieurs à 100 ms et de façon beaucoup plus fiable
* Permet l'optimisation des requêtes faites spécifiquement par DiaLog, notamment par la création d'indexes (impossible avec l'API)
* Moins de cas d'erreurs possibles

Inconvénients

* Coût opérationnel pour la gestion des tables de la BD TOPO : hébergement, mise à jour (~ annuelle), utilisation en développement, documentation...
* Coût financier d'hébergement : 14,40€ / mois pour le plan Start 1G.

### Approche détaillée

#### Mise en place

Il s'agirait de **télécharger le thème "Transports" de la BD TOPO** (environ 4.5 Go) et d'**ingérer dans la base les tables qui nous intéressent** telles que `voie_nommee` (1.8 Go), `route_numerotee_ou_nommee` (400 Mo) ou encore `troncon_de_route`.

Les tables sont fournies au format GeoPackage et peuvent être ingérées avec l'outil [**`ogr2ogr`**](https://gdal.org/programs/ogr2ogr.html) fourni par la librairie de référence [GDAL](https://gdal.org/index.html), laquelle [supporte PostgreSQL / PostGIS](https://gdal.org/drivers/vector/pg.html#driver-capabilities) :

```bash
ogr2ogr -f PostgreSQL "PG:postgresql://dialog_bdtopo:password@localhost:5432/dialog" /path/to/voie_nommee.gpkg
```

Pour assurer une **portabilité** maximum parmi l'équipe de développement (Linux, Windows...), on pourra appeler ogr2ogr via l'[image Docker de GDAL](https://github.com/OSGeo/gdal/pkgs/container/gdal).

Suite à une ingestion, des **indexes** judicieux seront créés pour accélérer l'exécution des requêtes.

#### Hébergement

Les tables de la BD TOPO seront hébergées sur une instance PostgreSQL dédiée.

Cette séparation entre données applicatives et BD TOPO qui s'accompagne d'identifiants distincts facilite la maintenance différenciée et participe des bonnes pratiques de sécurité (par ex, principe de moindre privilège).

#### Performance attendue

Des premiers tests via la [PR #677](https://github.com/MTES-MCT/dialog/pull/677) suggèrent les résultats suivants :

| Métrique | Avant | Après | Évolution |
|---|---|---|---|
| Temps de réponse, latence comprise (min, typique, max) | ~500ms, ~ 1-2s, > 10s (estimations) | Avec indexes : ~20ms, ~ 100ms, < 200 ms (estimations) ; Sans indexes : ~300ms, ~1s, < 2s (estimations) | > 20x plus rapide, moindre variabilité |
| Disponibilité (timeouts compris) | < 90% (estimation) | > 98% (garanti par le [SLA Scalingo](https://scalingo.com/service-level-agreement)) | Meilleure disponibilité |

D'une part le requêtage direct à PostgreSQL permet de bénéficier de l'excellente performance de ce SGBD, notamment combiné à des indexes conçus judicieusement (ce que l'API IGN ne permet pas de faire).

D'autre part, nous bénéficierons par extension de la qualité de service de l'hébergeur Scalingo.

#### Transformation des requêtes API WFS en requêtes aux tables BD TOPO

Les requêtes actuellement réalisées à l'API WFS peuvent être facilement traduites en SQL.

Par exemple, cette requête [GetFeature](https://docs.geoserver.org/stable/en/user/services/wfs/reference.html#wfs-getfeature) qui interroge la table `voie_nommee` ...

```http
GET https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&Version=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=code_insee='01234' HTTP/1.1
```

... correspondra à une requête SQL de ce type :

```sql
SELECT * FROM voie_nommee WHERE code_insee = '01234';
```

Ces requêtes pourront être réalisées avec l'infrastructure existante, à savoir Symfony avec l'ORM Doctrine. Ce dernier permet notamment de faire des requêtes SQL directement et de configurer une connexion distincte pour la BD TOPO.

#### Sécurisation des accès

L'hébergement de la BD TOPO dans une application Scalingo séparée permet une meilleure sécurisation que si la BD TOPO était hébergée directement au sein de la base DiaLog de production (par exemple), notamment du fait de limitations de Scalingo (l'option "read only" donnant accès à la base de données entières et non pas à seulement certaines tables).

Bien que les données BD TOPO soient d'ordre public, les identifiants BD TOPO devront être considérés comme sensibles pour réduire par exemple les risques d'attaques DDoS (surcharge en lecture de la BD TOPO par un acteur malveillant en ayant acquis les identifiants).

#### Mise à jour

Une mise à jour semi-manuelle (déclenchement manuel, exécution automatique) est envisageable puisque la BD TOPO est mise à jour peu fréquemment (une publication par an environ).

La mise à jour des données BD TOPO pourrait se faire par l'équipe de développement comme suit :

* Télécharger en local la nouvelle version du thème Transports ;
* Exécuter un script utilitaire qui mettra à jour les tables au fur et à mesure avec `ogr2ogr`.

Cette approche a des avantages et inconvénients par rapport au chargement complet des tables avant de remplacer les données existantes :

* Avantages :
  * Elle est plus simple, car le renommage d'une table n'est pas trivial (il faut penser à renommer ses indexes, séquences, et autres objets PostgreSQL associés).
  * Elle permet une économise de stockage significative, car le serveur n'a pas besoin d'être capable de stocker temporairement les tables en double (et donc d'être surdimensionné en temps normal).
* Inconvénients :
  * Cette approche n'est pas atomique. Si l'import d'un GeoPackage échoue, alors la table concernée n'aura que des données partielles. Cela peut produire des échecs de géocodage en production.

Les opérations de type VACUUM et/ou ANALYZE pertinentes seront effectuées après la mise à jour pour préparer le nouveau contenu à être requêté (mise à jour des statistiques utilisées par le planificateur de requête PostgreSQL).

**Vitesse de transfert**

La mise à jour prendra typiquement plusieurs minutes, en raison de l'upload du contenu des tables la BD TOPO vers Scalingo.

### Réversibilité

Si la qualité du service de l'API WFS de l'IGN s'améliore au point que le surcoût opérationnel (modeste mais non-nul) de gestion de notre hébergement BD TOPO n'est plus justifié, il sera toujours possible de récupérer le code de l'ancien géocodeur basé sur l'API WFS.

Les données étant les mêmes puisque toutes deux issues de la BD TOPO, on ne devrait pas observer d'incohérences.

## Références

* [BD TOPO](https://geoservices.ign.fr/bdtopo) (documentation, téléchargements)
* [GDAL](https://gdal.org/index.html), [Driver PostgreSQL / PostGIS pour GDAL](https://gdal.org/drivers/vector/pg.html), [ogr2ogr](https://gdal.org/programs/ogr2ogr.html)
