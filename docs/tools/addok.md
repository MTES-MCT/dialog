# Addok personnalisé

Ce dépôt permet de lancer localement une instance [Addok](https://github.com/addok/addok) personnalisée contenant les données de la BAN ainsi que des POI d'intersections issus des données OpenStreetMap.

Pour plus de contexte, voir [ADR-007](../adr/007_eudonet_paris_integration.md).

## Démarrage rapide

**Prérequis**

* Docker et Docker Compose
* Au moins 6 Go de RAM libres

Récupérez le bundle :

1. Téléchargez [addok-dialog-bundle.zip] (1.7 Go).
2. Décompressez le contenu dans `docker/addok/addok-data`.

    Depuis la racine du projet :

    ```
    unzip -d docker/addok/addok-data /path/to/addok-dialog-bundle.zip
    ```

[addok-dialog-bundle]: https://kdrive.infomaniak.com/app/share/184671/0193c893-8b54-48a4-aa4d-5cdf1fbe88a8

Démarrez ensuite l'instance Addok avec :

```bash
make addok_start
```

Faites un test :

```bash
curl "http://localhost:7878/search?q=Rue+de+la+concertation&type=poi"
```

Quand vous n'avez plus besoin d'Addok, arrêtez-le :

```bash
make addok_stop
```

## Recréer le bundle

Cette section explique comment recréer le fichier `addok-dialog-bundle.zip` contenant les données chargées dans Addok.

**Prérequis**

* Docker et Docker Compose
* Au moins 6 Go de RAM libres

1. Téléchargez [addok-france-bundle.zip](https://adresse.data.gouv.fr/data/ban/adresses/latest/addok/addok-france-bundle.zip) (1.7 Go) (bundle de base).
1. Décompressez le contenu dans `docker/addok/addok-data`.
1. Lancez la commande :

    ```bash
    make addok_bundle
    ```

    Cette commande effectue plusieurs opérations :

    * Réinitialisation des conteneurs Addok, afin de supprimer les données existantes
    * Téléchargement des jeux de données nécessaires au calcul des POI d'intersections ([ADMIN-EXPRESS - Découpage administratif](https://geoservices.ign.fr/adminexpress), [OpenStreetMap France - Geofabrik](https://download.geofabrik.de/europe/france.html), [Base officielle des postaux](https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/))
    * Calcul des POI d'intersections
    * Import des POI d'intersections dans Addok
    * Mise à jour du dump Redis
    * Création du bundle ZIP

    L'exécution prend typiquement plusieurs dizaines de minutes en raison de la taille des fichiers à télécharger (plusieurs Go).

    Il se peut que le téléchargement des données soit plus rapide en passant par votre navigateur. Vous trouverez les URLs à télécharger dans `docker/addok/run.sh`.

    Pour sauter les étapes de téléchargement (par exemple si vous avez déjà téléchargé les fichiers par le passé), utilisez :

    ```bash
    make addok_bundle NO_DOWNLOAD=1
    ```

1. Déposez le nouveau bundle dans le dossier partagé DiaLog sur le kDrive (lien sur le pad général).

1. Mettre à jour l'URL du lien [dialog-addok-bundle] dans la présente doc

## Mettre à jour les données sources

Pour mettre à jour les jeux de données utilisés pour le calcul des intersections, modifiez les URLs afférentes dans `docker/addok/extras.sh`.

Vous devrez ensuite [recréer le bundle](#recréer-le-bundle).
