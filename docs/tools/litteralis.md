# Litteralis

DiaLog dispose d'une intégration avec [Litteralis]([Litteralis](https://www.sogelink.com/solution/litteralis/)). Cette solution de gestion de réglementation de l'éditeur Sogelink est utilisée par de nombreuses collectivités notamment de plus grande taille.

## Description

L'intégration requête l'API WFS de Litteralis. Pour cela elle a besoin d'**identifiants** ("credentials" au format "user:password") configuré par la collectivité qui nous donne accès à ses données Litteralis. Elle a aussi besoin de l'**UUID** de l'organisation dans DiaLog.

L'intégration est "générique" au sens où elle peut être réutilisée pour plusieurs collectivités. Chaque collectivité a donc un peu de code pour faire le pont entre des variables d'environnement contenant les informations ci-dessus et l'intégration générique.

## Organisations enregistrées

Le `code` est à utiliser en remplacement de `<INTG>` (pour "intégration") dans les commandes qui suivent

| Code | Nom complet |
|---|---|
| `mel` | Métropole Européenne de Lille (département Nord 59, région Hauts-de-France) |
| `fougeres` | Ville de Fougères (sous-préfecture département Ille-et-Villaine 35, région Bretagne) |
| `lons_le_saunier` | Ville de Lons-le-Saunier (préfecture département Jura 39, région Bourgogne-Franche-Comté) |

## Exécuter l'intégration

L'intégration peut être exécutée à l'aide de commandes Symfony spécifiques à chaque collectivité.

**Pour l'import en prod** :

1. Récupérer le UUID de l'organisation en prod. Pour cela demander à un super-admin : l'UUID est visible dans l'édition d'une organisation sur la page Organisations du dashboard.
2. Créer un fichier `.env.prod.local` vide, et y définir :
    * `BDTOPO_DATABASE_URL`
    * `APP_<INTG>_ORG_ID=<ID>` où `<ID>` est l'UUID de l'organisation que vous venez de récupérer.
    * `APP_<INTG>_LITTERALIS_CREDENTIALS` avec les identifiants MEL au format `user:password` (les demander à un membre de l'équipe dev)
3. Ouvrir un [tunnel](./db.md#utiliser-une-db-scalingo-en-local) vers la DB de prod :

    ```bash
    ./tools/scalingodbtunnel dialog
    ```

    Copier l'URL qui s'affiche dans le terminal.

4. Dans `.env.prod.local`, ajouter `DATABASE_URL=URL` où `URL` est l'URL que vous venez de copier.
5. Lancer cette commande :

    ```bash
    make console CMD="app:<INTG>:import --env=prod"
    ```

    L'exécution prendra plusieurs minutes. Les logs d'exécution seront ajoutés au dossier `logs/litteralis/`. En cas d'exception la commande échouera. Un rapport final "pretty print" est affiché.

**Pour le dev local** : remplir `.env.local` au lieu de `.env.prod`, sauter les étapes 3 et 4 (utiliser votre DB locale), et ne pas inclure le flag `--env=prod`.

## Déploiement périodique automatique

Les données Litteralis des différentes organisations sont automatiquement intégrées en production tous les lundis à 16h00.

Cette automatisation est réalisée au moyen de [GitHub Actions](./github_actions.md) via un workflow par organisation.

La configuration passe par diverses variables d'environnement listées ci-dessous :

| Variable d'environnement | Configuration | Description |
|---|---|---|
| `APP_MEL_IMPORT_APP` | [Variable](https://docs.github.com/fr/actions/learn-github-actions/variables) au sens GitHub Actions | L'application Scalingo cible (par exemple `dialog` pour la production) |
| `APP_MEL_IMPORT_DATABASE_URL` | Secret | L'URL d'accès à la base de données par la CI (`./tools/scalingodbtunnel APP  --host-url`) |
| `APP_MEL_LITTERALIS_CREDENTIALS` | [Secret](https://docs.github.com/fr/actions/security-guides/using-secrets-in-github-actions) au sens GitHub Actions | Les identifiants d'accès à l'API Litteralis de la MEL |
| `APP_MEL_ORG_ID` | Variable | Le UUID de l'organisation "Métropole Européenne de Lille" dans l'environnement défini par `APP_MEL_IMPORT_APP` |
| `APP_FOUGERES_LITTERALIS_CREDENTIALS`, `APP_FOUGERES_ORG_ID` | Secrets | Idem que pour la MEL |
| `APP_LONS_LE_SAUNIER_LITTERALIS_CREDENTIALS`, `APP_LONS_LE_SAUNIER_ORG_ID` | Secrets | Idem que pour la MEL |
| `GH_SCALINGO_SSH_PRIVATE_KEY` | Secret | Clé SSH privée permettant l'accès à Scalingo par la CI |

## Références

* [ADR-010 - Litteralis](../adr/010_litteralis.md)
* [Ticket #658](https://github.com/MTES-MCT/dialog/issues/658) (Cas de la MEL)
* [PR #874](https://github.com/MTES-MCT/dialog/issues/874) (Implémentation initiale traitant le cas de la MEL)
