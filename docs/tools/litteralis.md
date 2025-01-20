# Litteralis

DiaLog dispose d'une intégration avec [Litteralis]([Litteralis](https://www.sogelink.com/solution/litteralis/)). Cette solution de gestion de réglementation de l'éditeur Sogelink est utilisée par de nombreuses collectivités, notamment celles de plus grande taille.

## Description

L'intégration requête l'API WFS de Litteralis pour extraire les emprises que DiaLog peut intégrer.

## Configuration

Les organisations à intégrer sont définies **dynamiquement** par la variable d'environnement `APP_LITTERALIS_ENABLED_ORGS`.

Par exemple :

```bash
APP_LITTERALIS_ENABLED_ORGS='["mel", "fougeres"]'
```

Pour chaque organisation qui y est indiquée, deux autres variables d'environnement doivent être définies : `APP_LITTERALIS_ORG_<NAME>_ID` et `APP_LITTERALIS_ORG_<NAME>_CREDENTIALS`, où `<NAME>` est à remplacer par le nom en majuscule. Exemple : `APP_LITTERALIS_ORG_MEL_ID`.

Dans GitHub Actions, les variables et secrets suivants sont configurés :

| Variable d'environnement | Variable ou Secret ? | Description |
|---|---|---|
| `APP_LITTERALIS_IMPORT_DATABASE_URL` | Secret | L'URL d'accès à la base de données par la CI (`./tools/scalingodbtunnel APP --host-url`) |
| `APP_LITTERALIS_ENABLED_ORGS` | Variable | Liste des organisations dont il faut intégrer les données Litteralis, au format array JSON |
| `APP_LITTERALIS_ORG_<NAME>_ID` | Secret | UUID de l'organisation (à définir pour chaque organisation `<NAME>` dans `APP_LITTERALIS_ENABLED_ORGS`). Peut être récupéré dans l'administration (demander à l'admin) ou dans le `<select>` du filtre Organisation de la [liste des arrêtés](https://dialog.beta.gouv.fr/regulations). |
| `APP_LITTERALIS_ORG_<NAME>_CREDENTIALS` | Secret | Identifiants d'accès à l'API Litteralis de l'organisation, au format `username:password` (à définir pour chaque organisation `<NAME>` dans `APP_LITTERALIS_ENABLED_ORGS`). es identifiants sont à activer dans Litteralis par la collectivité qui nous donne accès à ses données. |
| `GH_SCALINGO_SSH_PRIVATE_KEY` | Secret | Clé SSH privée permettant l'accès à Scalingo par la CI |

## Utilisation

### Avec GitHub Actions

L'import est exécuté **automatiquement** tous les lundis à l'aide d'un [workflow](../../.github/workflows/litteralis_import.yml) GitHub Actions.

L'import peut aussi être exécuté **manuellement** à l'aide du bouton "Workflow dispatch". Vous pouvez alors n'exécuter l'import que pour certaines collectivités en précisant le paramètre `enabled_orgs`, qui remplacera temporairement la variable `APP_LITTERALIS_ENABLED_ORGS` configurée sur le repo.

### En local

Pour le développement, l'import peut être exécuté en local en appelant la commande Symfony :

```bash
make console CMD="app:litteralis:import"
```

Cela lira les variables `APP_LITTERALIS_ENABLED_ORGS` et `APP_LITTERALIS_ORG_*` dans votre `.env.local`.

## Références

* [ADR-010 - Litteralis](../adr/010_litteralis.md)
* [Ticket #658](https://github.com/MTES-MCT/dialog/issues/658) (Cas de la MEL)
* [PR #874](https://github.com/MTES-MCT/dialog/issues/874) (Implémentation initiale traitant le cas de la MEL)
