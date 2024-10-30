# Metabase

Conformément aux usages de la communauté beta.gouv.fr, DiaLog utilise Metabase pour la collecte de statistiques relatives à son utilisation.

L'instance Metabase est accessible ici : https://dialog-metabase.osc-fr1.scalingo.io/

Vous devez disposer d'un compte pour y accéder. Demandez pour cela à un membre de l'équipe.

## Aperçu de l'installation

Le Metabase de DiaLog est hébergé sur Scalingo sous l'application `dialog-metabase`. (Demandez à un membre de l'équipe de vous ajouter à cette application pour y avoir accès.)

Cette application dispose de sa propre base de données où nous stockons les données nécessaires au calcul des indicateurs, conformément aux [recommendations Beta](https://doc.incubateur.net/communaute/les-outils-de-la-communaute/autres-services/metabase/metabase#connecter-metabase-a-une-base-de-donnees-anonymisee)

La collecte des données d'indicateurs est réalisée au moyen d'un [script](../../tools/metabase-export.sh). Ce script exécute des requêtes SQL depuis la base Metabase vers la base applicative. Pour cela un utilisateur `dialog_metabase` avec droits en lecture seule a été créé sur la base applicative (identifiants dans le Vaultwarden de l'équipe DiaLog).

## Lancer l'export depuis GitHub Actions

L'export Metabase peut être déclenché via [GitHub Actions](./github_actions.md) à l'aide du workflow [`metabase_export.yml`](../../.github/workflows/metabase_export.yml).

### Configuration de la GitHub Action

La configuration de la GitHub Action passe par diverses variables d'environnement listées ci-dessous :

| Variable d'environnement | Configuration | Description |
|---|---|---|
| `METABASE_EXPORT_SRC_APP` | [Variable](https://docs.github.com/fr/actions/learn-github-actions/variables) au sens GitHub Actions | `dialog` (pour la production) |
| `METABASE_EXPORT_SRC_DATABASE_URL` | [Secret](https://docs.github.com/fr/actions/security-guides/using-secrets-in-github-actions) au sens GitHub Actions | L'URL d'accès à la base de données applicative par la DB Metabase : utiliser la `METABASE_EXPORT_SRC_DATABASE_URL` de l'app `dialog` |
| `METABASE_EXPORT_DEST_APP` | Variable | `dialog-metabase` |
| `METABASE_EXPORT_DEST_DATABASE_URL` | Secret | L'URL d'accès à la base de données Metabase par la CI (`./tools/scalingodbtunnel dialog-metabase  --host-url --port 10001`) |
| `GH_SCALINGO_SSH_PRIVATE_KEY` | Secret | Clé SSH privée permettant l'accès à Scalingo par la CI |
