# Eudonet Paris

DiaLog s'intègre avec Eudonet Paris, le système de gestion d'arrêtés de circulation de la Ville de Paris.

Au 01/09/2023, l'intégration nécessite d'exécuter manuellement une commande d'import, voir [ADR-007](../adr/007_eudonet_paris_integration.md).

## Importer les arrêtés

**Préparation**

1. Lancez l'[instance Addok personnalisée](./addok.md)
1. Ajoutez ces variables d'environnement à votre fichier `.env.local` :
  * `APP_EUDONET_PARIS_CREDENTIALS=...`, en remplaçant `...` par les identifiants au format JSON.
    * Obtenez ces identifiants auprès de l'équipe.
  * `API_ADRESSE_BASE_URL=http://addok:7878`

**Exécution**

Lancez `make console CMD="app:eudonet_paris:import"`

Notes :

* Ce script envoie des requêtes à l'API Eudonet Paris
* Il récupère les arrêtés remplissant **toutes** les conditions suivantes :
  * Date de fin dans le futur ;
  * N'existe pas encore dans DiaLog (pas d'arrêté avec le même `identifier`).
    * **Remarque** : un arrêté peut avoir été traité par le passé, mais n'être pas encore dans DiaLog. Exemple : l'arrêté n'avait que des mesures de stationnement ou aucune des localisations n'a pu être ingérée.
    * **Remarque** : à ce jour, les modifications apportées côté Eudonet Paris à un arrêté déjà intégré à DiaLog ne sont pas récupérées.
* Les arrêtés sont récupérés, traités (dont géocodage des localisations), et intégrés à la base de données.
* Un fichier de log est créé dans `log/eudonet_paris/`.
  * Inspectez-le pour vérifier l'exécution de l'import.
  * Créez une PR pour enregistrer le nouveau fichier de log, faites-la relire, puis mergez-la.

**Pour un import sur staging ou en production**

* Dans `.env.local`, définissez la `DATABASE_URL` pointant vers la base de données cible (peut être récupéré sur Scalingo)
* Avant de lancer l'exécution, vérifier la prise en compte de `DATABASE_URL` avec : `make console CMD="debug:dotenv DATABASE_URL"`
