# Intégration de données

Cette page est destinée aux collectivités souhaitant importer des données depuis leur logiciel de saisie d'arrêtés vers DiaLog.

La mise en place d'une telle intégration nécessite une étroite collaboration et parfois des développements spécifiques de notre part en raison de la diversité des logiciels et des bases de données dont les collectivités peuvent être équipées. En dépit de cette diversité, la mise en place passe par plusieurs grandes étapes décrites ci-dessous.

Que deviennent les données une fois intégrées ? De quelles données la plateforme DiaLog a-t-elle besoin ? Sous quel format ? Suivez le guide !

## Étapes de la mise en place d'une intégration

| Etape | Qui | Fait quoi |
|---|---|---|
| 1 | Collectivité + Équipe DiaLog | Prise de contact et identification technique de la source de données (fichier unique, API...) et de sa fréquence de mise à jour |
| 2 | Collectivité | Mise à disposition d'un accès technique aux données sources (incluant d'éventuels identifiants d'authentification) |
| 3 | Équipe DiaLog | Test du bon fonctionnement de l'accès technique, évaluation des données sources et confirmation des possibilités d'import dans DiaLog le cas échéant |
| 4 | Équipe DiaLog | Développement ou adaptation du code nécessaire à l'import des données sources dans la plateforme DiaLog |
| 5 | Équipe DiaLog | Mise en ligne des données dans un environnement de recette afin de valider l'intégration, avec communication des statistiques d'intégration telles que le nombre d'arrêtés importés ou en erreur |
| 6 | Collectivité | Validation de l'intégration sur l'environnement de recette (par exemple par inspection de la carte des restrictions) |
| 7 | Équipe DiaLog | Mise en production de l'intégration et des données |

En fonction de la source de données, l'exécution de l'import de données pourra être programmé périodiquement (généralement toutes les semaines).

Après la mise en ligne initiale, l'intégration fera l'objet d'une amélioration continue en fonction des retours de la collectivité.

## Chemin de la donnée

Dans le cadre d'une intégration entre DiaLog et votre logiciel ou base de données, la donnée parcourra le chemin suivant.

### 1. Transfert des données de la source vers DiaLog

À cette étape, DiaLog interroge la source de données pour récupérer les données utiles à leur import dans DiaLog.

Le mode de récupération et la périodicité de l'import dépend des capacités de votre logiciel ou base de données contenant les arrêtés.

Cela peut se faire par exemple par une requête HTTP à une URL qui renvoie des données au format GeoJSON ou encore Shapefile.

Important : **les données renvoyées par la source doivent être structurées et immédiatement lisibles par une machine**, sous des formats tels que JSON, XML ou encore CSV. Cela exclut les sources de données renvoyant des arrêtés au format PDF, qui ne peuvent pas être importés à ce jour.

Les données obtenues à cette étape sont appelées "données candidates à l'import".

### 2. Transformation des données au format DiaLog

À cette étape, la donnée source est transformée pour en obtenir une version respectant le [modèle de données de DiaLog](../adr/002_mcd.md), afin qu'elle soit importée dans la base de données de la plateforme DiaLog elle-même.

Les opérations de transformation nécessaires varient en fonction de la source de données et nécessitent le plus souvent des développements informatiques spécifiques par l'équipe DiaLog. Si une intégration avec votre logiciel a déjà été réalisée par le passé pour une autre collectivité, il est possible que seules des adaptations mineures soient nécessaires.

La transformation au format DiaLog nécessite en tout cas de pouvoir obtenir les informations suivantes :

* Pour l'arrêté :
    * Identifiant de l'arrêté
    * Un intitulé ou des informations permettant d'en produire un
    * La nature de l'arrêté : permanent, temporaire
    * (Optionnel) L'objet de l'arrêté : travaux, événement, autre...
    * (Optionnel) URL d'accès à l'arrêté au format PDF
* Pour chaque mesure apparaissant dans l'arrêté :
    * Son type : circulation interdite, limitation de vitesse..
    * Les véhicules concernés : tous, ou seulement certains (tonnage, restrictions de gabarit...), éventuellement en excluant certains autres (taxis, véhicules d'urgence...)
    * La ou les périodes d'application de la mesure : date et heure de début, date et heure de fin, (optionnel) jours de la semaine concernés (lundi, mardi...), (optionnel) horaires applicables
    * Pour chaque localisation où s'applique la mesure :
        * _(De préférence)_ **Géométrie** : de préférence fournie directement au format GeoJSON.
        * _(À défaut)_ Toutes les informations précises permettant de calculer une géométrie GeoJSON par une opération de géocodage (numéro d'adresse, nom de voie, code Insee de la commune, numéro de route départementale ou nationale, identifiants de points kilométriques...).

Les arrêtés dont les données sources ne permettent pas de les importer de façon fidèle dans DiaLog ne seront pas importés et ne seront a fortiori pas mis à disposition sur les différents canaux de réutilisation (carte, export DATEX II, export CIFS). Voir également la [documentation API](./api.md) pour la création d’arrêtés et les exports.

### 3. Mise à disposition pour réutilisation

Une fois importée dans la base de données DiaLog, la donnée est automatiquement mise à disposition sur les canaux suivants :

* La carte des restrictions : https://dialog.beta.gouv.fr/carte
* L'export au standard DATEX II : https://dialog.beta.gouv.fr/api/regulations.xml
    * Cet export est publié en open data sur la plateforme data.gouv : https://www.data.gouv.fr/fr/datasets/64947a4af5faf2f1f9eee299/
    * Il est aussi publié sur le Point d'Accès National (PAN) transport.data.gouv.fr : https://transport.data.gouv.fr/datasets/base-de-donnees-nationale-de-la-reglementation-de-circulation
* L'export CIFS (Waze) : https://dialog.beta.gouv.fr/api/regulations/cifs.xml
    * :warning: En raison des limites de ce format propriétaire utilisé par Waze, seules les interdictions de circulation temporaires qui concernent tous les véhicules peuvent être envoyés à Waze. Cela signifie que des restrictions zone 30, des restrictions spécifiques aux poids lourds ou encore des interdictions de circulation permanentes ne pourront pas être transmises à Waze.
