# 011 - Découpage administratif

* Création : 2025-03-04
* Personnes impliquées : Mathieu Marchois (auteur principal), équipe DiaLog (relecture)
* Statut : BROUILLON

## Contexte

Notre application nécessite l'accès aux contours géographiques des différentes entités administratives françaises (communes, départements, régions, EPCI) pour associer ces contours aux organisations identifiées par leur SIRET.

Actuellement, deux solutions principales sont disponibles :

* [ADMIN Express](https://geoservices.ign.fr/adminexpress) : une base de données de l'IGN contenant les contours administratifs, disponible en téléchargement au format Shapefile ou GeoPackage
* [API Découpage administratif](https://guides.data.gouv.fr/reutiliser-des-donnees/utiliser-les-api-geographiques/utiliser-lapi-decoupage-administratif) : une API REST proposée par la DINUM (Etalab) permettant d'accéder aux données géographiques des entités administratives françaises.

Il est nécessaire de déterminer quelle solution est la plus adaptée à nos besoins, notamment en termes de performance, fiabilité, et facilité d'intégration.

## Décision

Nous utiliserons l'API Découpage administratif comme source principale de données géographiques administratives, complétée par une approche hybride de mise en cache et de stockage sélectif dans notre base de données PostgreSQL/PostGIS.

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

### Option 2 - Utilisation de l'API Découpage administratif

Cette option consisterait à utiliser exclusivement l'API Découpage administratif pour obtenir les contours à la demande.

Avantages :

* Aucune gestion de données volumineuses
* Données toujours à jour
* Simplicité d'implémentation initiale
* Pas de processus de mise à jour à gérer

Inconvénients :

* Dépendance à la disponibilité de l'API
* Temps de réponse variables selon la charge de l'API
* Limitation possible du nombre de requêtes

### Option 3 - Approche hybride (solution retenue)

Cette option consiste à utiliser l'API Découpage administratif tout en stockant localement les contours les plus fréquemment utilisés.

Avantages :

* Équilibre entre performance et facilité de maintenance
* Temps de réponse très rapides pour les entités mises en cache
* Résilience partielle en cas d'indisponibilité de l'API
* Optimisation du stockage (uniquement les données pertinentes)

Inconvénients :

* Complexité d'implémentation légèrement plus élevée
* Nécessité de gérer la cohérence entre cache/DB et API

### Approche détaillée

L'approche hybride proposée se compose de plusieurs éléments clés:

1. **Extension de l'entité Organization existante**:
   - Ajout d'un champ `geometry` pour stocker directement le contour géographique de l'organisation
   - Stockage des codes administratifs associés (codeInsee, codeDepartement, codeRegion, codeEpci)
   - Ajout d'un indicateur de date de dernière mise à jour du contour

2. **Gestion des contours**:
   - Interface avec l'API Découpage administratif
   - Logique de récupération avec hiérarchie de priorité: DB locale → cache → API externe

3. **Correspondance SIRET → codes administratifs**:

4. **Processus de mise à jour**:
   - Rafraîchissement semestrielle de tous les coutours administratif stockés dans la table Organization via une commande Symfony.
