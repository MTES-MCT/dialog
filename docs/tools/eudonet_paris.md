# Eudonet Paris

DiaLog s'intègre avec Eudonet Paris, le système de gestion d'arrêtés de circulation de la Ville de Paris.

Au 01/09/2023, l'intégration nécessite d'exécuter manuellement une commande d'import, voir [ADR-007](../adr/007_eudonet_paris_integration.md).

## Importer les arrêtés

### Préparation

Ajoutez ces variables d'environnement à votre fichier `.env.local` :

* `APP_EUDONET_PARIS_CREDENTIALS=...`, en remplaçant `...` par les identifiants au format JSON.
  * Obtenez ces identifiants auprès de l'équipe.
* `APP_EUDONET_PARIS_ORG_ID=...`, en remplaçant `...` par le UUID de l'organisation de la Ville de Paris, où les arrêtés seront ajoutés
  * En local ou en développement, créez une organisation "Ville de Paris" dans l'[admin](./admin.md) et recopiez son ID (visible dans son URL d'édition).

### Exécution

Lancez `make console CMD="app:eudonet_paris:import"`

La première exécution devrait prendre quelques minutes.

Inspectez le fichier de log créé dans `logs/` pour vérifier l'exécution de l'import (il est alimenté pendant l'exécution).

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

### Pour un import sur staging ou en production

1. [Démarrez un tunnel SSH vers la base de données cible](./db.md#utiliser-une-db-scalingo-en-local).
2. Dans `.env.prod.local` :
  * Reportez la ligne `DATABASE_URL` affichée après avoir démarré le tunnel SSH.
  * Définissez `APP_EUDONET_PARIS_ORG_ID` avec l'ID de l'organisation "Ville de Paris" en prod (peut être récupéré auprès de l'admin)
  * Ajoutez aussi `APP_EUDONET_PARIS_CREDENTIALS` et `API_ADRESSE_BASE_URL` (voir [Préparation](#préparation)).
3. Avant de lancer l'exécution :
  * Vérifiez la prise en compte des variables d'environnement :

    ```bash
    make console CMD="debug:dotenv --env=prod DATABASE_URL"
    make console CMD="debug:dotenv --env=prod APP_EUDONET_PARIS_"
    make console CMD="debug:dotenv --env=prod API_ADRESSE_"
    ```

4. Lancez l'exécution avec :

  ```bash
  make console CMD="--env prod app:eudonet_paris:import"
  ```

5. Après l'exécution :
  * Vérifiez l'exécution en inspectant le fichier `import.prod-*.log` alimenté pendant l'import.
  * Commentez les variables dans `.env.prod.local` pour éviter de les réutiliser par mégarde jusqu'au prochain import.

## Déploiement périodique automatique

Les données Eudonet Paris sont automatiquement intégrées en production tous les lundis à 16h30.

Cette automatisation est réalisée au moyen de [GitHub Actions](./github_actions.md) via le workflow [`eudonet_paris_import.yml`](../../workflows/eudonet_paris_import.yml).

La configuration passe par diverses variables d'environnement résumées ci-dessous :

| Variable d'environnement | Configuration | Description |
|---|---|---|
| `EUDONET_PARIS_IMPORT_APP` | [Variable](https://docs.github.com/fr/actions/learn-github-actions/variables) au sens GitHub Actions | L'application Scalingo cible (par exemple `dialog` pour la production) |
| `EUDONET_PARIS_IMPORT_CREDENTIALS` | [Secret](https://docs.github.com/fr/actions/security-guides/using-secrets-in-github-actions) au sens GitHub Actions | Les identifiants d'accès à l'API Eudonet Paris |
| `EUDONET_PARIS_IMPORT_DATABASE_URL` | Secret | L'URL d'accès à la base de données par la CI (voir ci-dessous) |
| `EUDONET_PARIS_IMPORT_ORG_ID` | Variable | Le UUID de l'organisation "Ville de Paris" dans l'environnement défini apr `EUDONET_PARIS_IMPORT_APP` |
| `GH_SCALINGO_SSH_PRIVATE_KEY` | Secret | Clé SSH privée permettant l'accès à Scalingo par la CI |
