# Eudonet Paris

DiaLog s'intègre avec Eudonet Paris, le système de gestion d'arrêtés de circulation de la Ville de Paris.

Au 01/09/2023, l'intégration nécessite d'exécuter manuellement une commande d'import, voir [ADR-007](../adr/007_eudonet_paris_integration.md).

## Importer les arrêtés

### Préparation

1. Lancez l'[instance Addok personnalisée](./addok.md)
1. Ajoutez ces variables d'environnement à votre fichier `.env.local` :
  * `APP_EUDONET_PARIS_CREDENTIALS=...`, en remplaçant `...` par les identifiants au format JSON.
    * Obtenez ces identifiants auprès de l'équipe.
  * `APP_EUDONET_PARIS_ORG_ID=...`, en remplaçant `...` par le UUID de l'organisation de la Ville de Paris, où les arrêtés seront ajoutés
    * En local ou en développement, créez une organisation "Ville de Paris" dans l'[admin](./admin.md) et recopiez son ID (visible dans son URL d'édition).
  * `API_ADRESSE_BASE_URL=http://addok:7878`

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

Les données Eudonet Paris sont automatiquement intégrées en production tous les lundis à 17h00.

Cette automatisation est réalisée au moyen de GitHub Actions (voir [`eudonet_paris_import.yml`](../../workflows/eudonet_paris_import.yml)).

### Accès SSH de GitHub Actions à la base de données sur Scalingo

Cette GitHub Action a besoin d'un accès SSH à la base de données hébergée chez Scalingo.

Pour cela des clés SSH ont été générées comme suit :

```bash
ssh-keygen -t ed25519 -q -N "" -f ~/.ssh/id_dialog_gh_scalingo
```

La clé publique `~/.ssh/id_dialog_gh_scalingo.pub` ainsi générée a été enregistrée sur Scalingo dans la section [Mes clés SSH](https://dashboard.scalingo.com/account/keys) du compte Scalingo professionnel de @florimondmanca.

> 💡 Pour renouveler les clés, ou en cas de perte, de nouvelles clés peuvent être régénérées en utilisant la méthode ci-dessus, puis rattachées au compte de toute personne ayant un accès "Collaborator" sur l'app Scalingo `dialog`.

La clé privée a été ajoutée comme secret `$GH_SCALINGO_SSH_PRIVATE_KEY` au dépôt GitHub et est utilisée par la GitHub Action.

L'accès à la base de données lors de l'import se fait via un [tunnel chiffré Scalingo](https://doc.scalingo.com/platform/databases/access#encrypted-tunnel).

* L'URL de base de données résultant a été ajouté comme secret `$EUDONET_PARIS_IMPORT_DATABASE_URL`.
* La valeur de ce secret doit être la `DATABASE_URL` de production où l'on remplace le `host:port` par `127.0.0.1:10000` afin de pointer sur le DB tunnel Scalingo (le port `10000` est hardcodé dans la GitHub Action).

### Données Addok

L'intégration Eudonet Paris a besoin de faire tourner l'[instance Addok personnalisée](./addok.md) en local.

Il faut donc que la GitHub Action télécharge le fichier ZIP contenant les données (1.6 Go environ) hébergé sur le kDrive de Fairness.

Cela est fait par le script `tools/download_addok_bundle.sh`. Pour cela une clé d'API Infomaniak a été créée par @florimondmanca et enregistrée dans le secret `EUDONET_PARIS_KDRIVE_TOKEN`.

L'identifiant du fichier sur kDrive est stocké dans le secret `EUDONET_PARIS_KDRIVE_FILE_ID`.

#### Mise à jour des données Addok

Si un nouveau bundle Addok est stocké sur le kDrive, récupérer le FileID (visible dans l'URL de partage du fichier) et mettre à jour le secret `EUDONET_PARIS_KDRIVE_FILE_ID`.

Le ZIP est mis en cache après le premier téléchargement.
