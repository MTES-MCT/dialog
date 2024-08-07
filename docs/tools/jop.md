# Données JOP 2024

DiaLog intègre les données des zones JOP pour les Jeux Olympiques de Paris 2024 (JOP 2024).

## Description

Un fichier GeoJSON préparé à partir du Shapefile de la Préfecture de Police de Paris est stocké dans le dépôt : [data/jop/zones.geojson](../../data/jop/zones.geojson). Voir son [README.md](../../data/jop) pour savoir comment il a été préparé.

L'intégration JOP consiste à traiter ce GeoJSON pour créer un unique arrêté `JOP2024-ZONES` contenant les différentes mesures et localisations. La zone JOP (bleue, rouge, grise) correspondant à la localisation est indiquée en remplissant le champ `otherExemptedTypeText`.

## Mettre à jour les données

Si `zones.geojson` change ou si l'arrêté `JOP2024-ZONES` doit être recalculé pour une quelconque autre raison, voici comment procéder pour le mettre à jour.

1. Récupérer le UUID de l'organisation "Préfecture de Police de Paris" (PP) en prod. Pour cela demander à un super-admin : l'UUID est visible dans l'URL de la page de l'organisation dans l'admin.
2. Créer un fichier `.env.prod.local` vide, et y définir :
    * `BDTOPO_DATABASE_URL`
    * `APP_JOP_ORG_ID=ID`, où `ID` est l'UUID de la PP que vous venez de récupérer.
3. Ouvrir un [tunnel](./db.md#utiliser-une-db-scalingo-en-local) vers la DB de prod :

    ```bash
    ./tools/scalingodbtunnel dialog
    ```

    Copier l'URL qui s'affiche dans le terminal.

4. Dans `.env.prod.local`, ajouter `DATABASE_URL=URL` où `URL` est l'URL que vous venez de copier.
5. Lancer cette commande :

    ```bash
    make console CMD="app:jop:import --env=prod"
    ```

    L'exécution prendra généralement quelques minutes. Les logs d'exécution seront ajoutés au dossier `logs/jop/`. En cas d'exception la commande échouera.

## Références

* [Ticket #839](https://github.com/MTES-MCT/dialog/issues/839)
* [PR #847](https://github.com/MTES-MCT/dialog/pull/847)
