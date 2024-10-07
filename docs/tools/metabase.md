# Metabase

Conformément aux usages de la communauté beta.gouv.fr, DiaLog utilise Metabase pour la collecte de statistiques relatives à son utilisation.

L'instance Metabase est accessible ici : https://dialog-metabase.osc-fr1.scalingo.io/

Vous devez disposer d'un compte pour y accéder. Demandez pour cela à un membre de l'équipe.

## Aperçu de l'installation

Le Metabase de DiaLog est hébergé sur Scalingo sous l'application `dialog-metabase`. (Demandez à un membre de l'équipe de vous ajouter à cette application pour y avoir accès.)

Conformément aux recommendations Beta, une base de données dédiée aux données Metabase a été créée afin de la séparer des données de production. Elle est hébergée sur l'application `dialog-metabase` via un add-on PostgreSQL, comme indiqué dans [cette doc](https://doc.incubateur.net/communaute/les-outils-de-la-communaute/autres-services/metabase/metabase#connecter-metabase-a-une-base-de-donnees-anonymisee).

## Lancer l'export

L'export Metabase peut être déclenché via [GitHub Actions](./github_actions.md) à l'aide du workflow [`metabase_export.yml`](../../.github/workflows/metabase_export.yml).

La configuration passe par diverses variables d'environnement listées ci-dessous :

| Variable d'environnement | Configuration | Description |
|---|---|---|
| `METABASE_EXPORT_SRC_APP` | [Variable](https://docs.github.com/fr/actions/learn-github-actions/variables) au sens GitHub Actions | `dialog` (pour la production) |
| `METABASE_EXPORT_DEST_APP` | [Variable](https://docs.github.com/fr/actions/learn-github-actions/variables) au sens GitHub Actions | `dialog-metabase` |
| `METABASE_EXPORT_SRC_DATABASE_URL` | [Secret](https://docs.github.com/fr/actions/security-guides/using-secrets-in-github-actions) au sens GitHub Actions | L'URL d'accès à la base de données applicative par la CI (`./tools/scalingodbtunnel dialog  --host-url`) |
| `METABASE_EXPORT_DEST_METABASE_DATABASE_URL` | [Secret](https://docs.github.com/fr/actions/security-guides/using-secrets-in-github-actions) au sens GitHub Actions | L'URL d'accès à la base de données Metabase par la CI (`./tools/scalingodbtunnel dialog-metabase  --host-url --port 10001`) |
| `GH_SCALINGO_SSH_PRIVATE_KEY` | Secret | Clé SSH privée permettant l'accès à Scalingo par la CI |
