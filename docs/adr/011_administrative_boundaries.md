# 011 - Découpage administratif

* Création : 2025-03-04
* Personnes impliquées : Mathieu Marchois (auteur principal), équipe DiaLog (relecture)
* Statut : ACCEPTÉ

## Contexte

Notre application nécessite l'accès aux contours géographiques des différentes entités administratives françaises (communes, départements, régions, EPCI) pour associer ces contours aux organisations identifiées par leur SIRET.

Actuellement, deux solutions principales sont disponibles :

* [ADMIN Express](https://geoservices.ign.fr/adminexpress) : une base de données de l'IGN contenant les contours administratifs, disponible en téléchargement au format Shapefile ou GeoPackage
* [API Découpage administratif](https://guides.data.gouv.fr/reutiliser-des-donnees/utiliser-les-api-geographiques/utiliser-lapi-decoupage-administratif) : une API REST proposée par la DINUM (Etalab) permettant d'accéder aux données géographiques des entités administratives françaises. Voir le [Github](https://github.com/datagouv/api-geo).

Il est nécessaire de déterminer quelle solution est la plus adaptée à nos besoins, notamment en termes de performance, fiabilité, et facilité d'intégration.

## Décision

Nous utiliserons l'API Découpage administratif comme source principale de données géographiques administratives, complétée par une approche hybride de stockage sélectif dans notre base de données PostgreSQL/PostGIS.

Conséquences :

* Un service dédié sera créé pour interroger l'API Découpage administratif
* Les contours géographiques seront stockés en base de données et mis à jour semestriellement.
* Un mécanisme de correspondance entre SIRET et codes géographiques sera implémenté via l'API Sirene.

## Options envisagées

### Option 1 - Utilisation de la base ADMIN Express

Cette option consisterait à télécharger l'intégralité de la base ADMIN Express et à l'importer dans notre base PostgreSQL/PostGIS.

Avantages :

* Accès direct aux données sans dépendance à une API externe
* Possibilité d'optimiser les requêtes via des index spécifiques
* Temps de réponse potentiellement meilleurs pour des requêtes complexes
* Disponibilité garantie même en cas de coupure réseau

Inconvénients :

* Volume de données important à stocker et gérer (~plusieurs GB)
* Processus d'importation initial et de mise à jour complexe
* Nécessité de gérer soi-même les mises à jour (1 à 2 fois par an)
* Surcoût en termes de stockage et de maintenance

### Option 2 - Utilisation de l'API Découpage administratif (solution retenue)

Cette option consisterait à utiliser l'API Découpage administratif pour obtenir les contours tout en stockant localement en DB ceux qui sont les plus fréquemment utilisés.

L'API nous permet de récupérer les informations concernant :
* Commune https://geo.api.gouv.fr/communes?codePostal=93400&fields=code,nom,siren,contour
* Département : https://geo.api.gouv.fr/communes?codeDepartement=93&fields=code,nom,siren,contour
* Région : https://geo.api.gouv.fr/communes?codeRegion=11&fields=code,nom,siren,contour
* EPCI : https://geo.api.gouv.fr/epcis?nom=Lille&fields=code,nom,contour

Avantages :

* Aucune gestion de données volumineuses
* Équilibre entre performance et facilité de maintenance
* Simplicité d'implémentation initiale
* Résilience partielle en cas d'indisponibilité de l'API
* Optimisation du stockage (uniquement les données pertinentes)

Inconvénients :

* Dépendance à la disponibilité de l'API
* Temps de réponse variables selon la charge de l'API
* Limitation possible du nombre de requêtes
* Mise à jour des contours stockés localement semestriellement

Cette solution se compose de plusieurs éléments clés:

1. **Extension de l'entité Organization existante**:
   - Ajout d'un champ `geometry` pour stocker directement le contour géographique de l'organisation
   - Stockage des codes administratifs associés dans deux nouvelles colonnes : code et codeType (insee, departement, region, epci)
   - Ajout d'un indicateur de date de dernière mise à jour du contour

2. **Gestion des contours**:
   - Interface avec l'API Découpage administratif
   - Stockage des contours dans la table Organization

3. **Correspondance SIRET → codes administratifs**:
   - Utilisation de l'[API recherche entreprise](https://recherche-entreprises.api.gouv.fr/search?q=20009320100016&est_collectivite_territoriale=true) qui nous permet de récupérer les informations d'une organisation via son SIRET.
   Dans l'exemple ici de la Métropole de Lille : `$codeInsee = $data['results'][0]['siege']['commune'] ?? null`

4. **Processus de mise à jour**:
   - Rafraîchissement semestrielle de tous les coutours administratif stockés dans la table Organization via une commande Symfony.

## Simplification des géométries

Pour optimiser le stockage et les performances d'affichage des contours administratifs, nous avons implémenté une stratégie de simplification des géométries adaptée à chaque type d'entité administrative.

### Problématique

Les contours administratifs bruts fournis par l'API Découpage administratif peuvent contenir un nombre très élevé de points, ce qui entraîne :
- Un volume de données important à stocker en base
- Des temps de chargement plus longs pour l'affichage des cartes
- Une consommation accrue de bande passante

### Solution retenue

Nous avons mis en place une simplification différenciée selon le type d'entité administrative, en utilisant la fonction `ST_SimplifyPreserveTopology` de PostGIS :

| Type d'entité | Facteur de simplification | Impact approximatif |
|---------------|---------------------------|---------------------|
| Commune (INSEE) | 0 (pas de simplification) | 0 |
| Département | 0.001 | ~110m |
| Région | 0.003 | ~210m |
| EPCI | 0.002 | ~160m |

Cette approche permet de :
- Conserver une précision élevée pour les communes
- Réduire significativement le poids des géométries pour les grandes entités (comme les régions)
- Maintenir un bon équilibre entre précision visuelle et performance
