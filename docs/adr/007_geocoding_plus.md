# 007 - Extension du service de géocodage

* Date : 2023-08-22
* Personnes impliquées : Florimond Manca (auteur principal), Mathieu Marchois et Léa Lefoulon (relecture technique), équipe DiaLog (relecture et commentaires)
* Statut : BROUILLON <!-- [BROUILLON|ACCEPTÉ|REJETÉ|DÉPRÉCIÉ] -->

## Contexte

DiaLog utilise jusqu'ici l'[API Adresse](https://adresse.data.gouv.fr/api-doc/adresse) pour le géocodage des localisations référencées par numéros de rue. Voir [ADR-005](./005_geocoding.md).

Des arrêtés que DiaLog a vocation à intégrer contiennent des localisations sous forme d'une section de voie délimitée par des intersections avec d'autres voies.

Ce besoin est notamment apparu dans le cadre du travail sur un [connecteur avec Eudonet Paris (#343)](https://github.com/MTES-MCT/dialog/issues/343).

L'API Adresse ne contient que les données de la [Base Adresse Nationale (BAN)](https://adresse.data.gouv.fr/), c'est-à-dire des POI représentant des numéros localisés sur des voies. Elle ne permet pas de géocoder une intersection, c'est-à-dire d'obtenir les coordonnées géographiques du point d'intersection entre deux voies.

Pour concrétiser le connecteur Eudonet Paris, mais probablement aussi d'autres connecteurs, il nous fallait donc supplémenter l'API Adresse.

## Décision

## Conséquences

* Un dépôt `MTES-MCT/dialog-addok` sera créé. Il contiendra le code source et la configuration pour le build et le déploiement de l'instance Addok de DiaLog.
* L'instance Addok de DiaLog sera déployée sur Scalingo à l'adresse `https://addok.dialog.beta.gouv.fr`.

## Options envisagées

### Option 1 - Se contenter de l'API Adresse

L'API Adresse ne contient que les données de la BAN.

Dans cette option, on ne peut donc pas intégrer d'arrêtés issus d'outils externes qui seraient définis par des intersections.

### Option 2 - Instance Addok avec données BAN et intersections obtenues à partir d'OpenStreetMap France

Dans cette option, on pré-calcule les intersections sur le territoire français à l'aide de données OpenStreetMap France sous forme de POI. Ces POI sont intégrés à une instance [Addok](https://github.com/addok/addok) personnalisée avec un label du type : `Voie A / Voie B, Code postal Ville`. Addok est le logiciel libre qui propulse l'API Adresse (API Adresse = Addok + données BAN).

**Avantages**

* Cela peut fonctionner : voir [#439](https://github.com/MTES-MCT/dialog/pull/439) pour un setup local.
* Si les données sont correctement packagées, la mise à jour des données d'Addok peut être assez simple : télécharger les nouveaux fichiers .db / .rdb, puis relancer Addok.

**Risques**

* Charge liée au déploiement et à la gestion du package de données d'intersections (build, hébergement, versionage).
* Risque d'incohérences car OpenStreetMap France ne se base pas sur la BAN mais sur les contributions de ses membres.
* Addok n'a pas été développé pour gérer des intersections. Le format `Voie A / Voie B, Code postal Ville` est ad-hoc, voire un _hack_. On ne pourrait pas demander l'intersection entre deux voies de villes différentes par exemple, ce qui pourrait être un frein à un cas d'usage intercommunal / métropole.

### Option 3 - Instance Addok avec données BAN et intersections BD TOPO

La [BD TOPO](https://geoservices.ign.fr/bdtopo) maintenue par l'IGN contient les linéaires de voies sur le territoire français.

À ce jour, il n'existe pas d'API prête à l'emploi pour géocoder une intersection grâce à la BD TOPO.

Cette option nécessiterait de développer une telle API.

**Hypothèse** : la BD TOPO serait capable de répondre à des requêtes géographiques d'intersections sans pré-calcul préalable.

* Il faudrait peut-être faire en 2 étapes : obtenir le centroïde et le nom normalisé des rues avec l'API Adresse, puis interroger la BD TOPO.
* Cf Jérôme Desboeufs (équipe API Adresse) : l'équipe NexSIS (pompiers) "utilise Addok pour chercher les voies puis le référentiel BD TOPO pour coder les intersections". L'équipe NexSIS peut être contactée sur le 'Slack Adresse'.

**Avantages**

* _(Hytpohèse)_ La BD TOPO contenant les linéaires de rues, on peut obtenir l'intersection entre n'importe quel couple de voies, y compris si elles sont dans des villes différentes (à la frontière).
* On retrouve les données de la BAN dans la BD TOPO. Il y a donc moins de risques d'incohérences qu'avec OpenStreetMap France.
* Déploiement simplifié : pas de package de données à gérer nous-mêmes.
* Mise à jour simplifiée : télécharger et importer dans PostgreSQL la nouvelle version de la BD TOPO.
* On pourrait aussi utiliser cette API pour obtenir un linéaire plus détaillé des voies (dans le cas où les GPS ne sauraient se contenter d'un linéaire composé uniquement d'un point de départ et d'un point d'arrivée)

**Risques**

* Nécessite de développer et maintenir une "API intersections BD TOPO", ce qui est techniquement hors scope DiaLog et aurait même vocation à être porté à un niveau plus large (possibilité de réutilisation par d'autres projets). Pour cela, télécharger et déployer la BD TOPO sur un serveur PostgreSQL (la BD TOPO pesant environ 30 Go), puis développer et y déployer une API web d'interrogation de cette base de données, idéalement la plus agnostique à DiaLog possible pour faciliter sa réutilisation.

## Questions ouvertes

### Géocodeur IGN

Mathieu Fernandez a eu vent d'un géocodeur en phase bêta à l'IGN. Quelle suite donner ?

## Références

* [Discussion sur mattermost.incubateur.net avec l'équipe API Adresse](https://mattermost.incubateur.net/betagouv/pl/tmwww1q9tfdq9cyu38497ae6xy)
