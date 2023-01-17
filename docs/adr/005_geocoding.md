# 005 - Choix d'un service de géocodage

* Date : 2023-01-17
* Personnes impliquées : Florimond Manca (auteur principal), Mathieu Marchois (relecture technique), équipe DiaLog (relecture et commentaires)
* Statut : BROUILLON <!-- [BROUILLON|ACCEPTÉ|REJETÉ|DÉPRÉCIÉ] -->

## Contexte

Dans le cadre du [MVP](https://github.com/MTES-MCT/dialog/milestone/1), il a été décidé de modéliser la localisation où s'applique une réglementation par une portion de rue. La portion de rue est représentée par : code postal, ville, rue, numéro de début, numéro de fin. (Voir [#43](https://github.com/MTES-MCT/dialog/issues/43) pour plus d'informations.)

Étant donnés deux points de coordonnées géographiques, le format de données DATEX II (voir [ADR-001](./001_exchangeformat.md)) permet de représenter une portion linéaire de route par l'entité `LinearWithinLinearElement`.

L'approche technique est donc de transformer l'adresse de début et l'adresse de fin (adresse + numéro de fin) en coordonnées géographiques en vue de l'intégrer à l'export DATEX II et _in fine_ les mettre à disposition des GPS.

Cette opération adresse => coordonnées géographiques s'appelle le **géocodage** (_geocoding_ en anglais).

Il s'agit donc de décider quel service de géocodage utiliser.

## Décision

<!-- Nous avons décidé d'utiliser l'[API Géocodage 2.0]() (TODO) de l'[IGN](https://www.ign.fr). Cette API fait partie des [Géoservices](https://geoservices.ign.fr) de l'IGN. -->

## Conséquences

<!-- * Une intégration technique avec l'API Géocodage 2.0 sera réalisée. -->
<!-- * Comme pour toute intégration d'API tierce, un soin particulier sera apporté à la gestion des erreurs : réponse inattendue, service indisponible. -->
<!-- * L'API étant ouverte, il n'y aura pas d'identifiants à générer. -->
* Les [limites inhérentes au géocodage](https://guides.etalab.gouv.fr/apis-geo/1-api-adresse.html#les-limites-du-geocodage) seront prises en compte dans l'implémentation côté DiaLog.

## Options envisagées

### Option 1 - API Adresse

Site web : https://api.gouv.fr/les-api/base-adresse-nationale

**Description**

L'API Adresse permet de faire du géocodage.

Les données proviennent de la [Base Adresse Nationale (BAN)](https://adresse.data.gouv.fr/) (pour les communes qui disposent d'une Base Adresse Locale), ou d'autres bases comme l'IGN, La Poste, la DGFIP, etc (pour les autres communes). Le moteur de calcul est le moteur de recherche open source [Addok](https://github.com/addok/addok).

**Utilisation**

Voir la [Documentation](https://adresse.data.gouv.fr/api-doc/adresse)

Exemple de requête / réponse :

```bash
curl "https://api-adresse.data.gouv.fr/search/?q=17+route+lac+44260+Savenay&limit=1&autocomplete=0" 
```

```json
{
  "type": "FeatureCollection",
  "version": "draft",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [
          -1.938313,
          47.358325
        ]
      },
      "properties": {
        "label": "17 Route du Lac 44260 Savenay",
        "score": 0.9620518181818181,
        "housenumber": "17",
        "id": "44195_0800_00017",
        "name": "17 Route du Lac",
        "postcode": "44260",
        "citycode": "44195",
        "x": 327487.03,
        "y": 6706984.59,
        "city": "Savenay",
        "context": "44, Loire-Atlantique, Pays de la Loire",
        "type": "housenumber",
        "importance": 0.58257,
        "street": "Route du Lac"
      }
    }
  ],
  "attribution": "BAN",
  "licence": "ETALAB-2.0",
  "query": "17 Route du Lac 44260 Savenay",
  "filters": {},
  "limit": 1
}
```

Les coordonnées (longitude, latitude) peuvent alors être obtenues par :

```php
$lonLat = $data['features'][0]['geometry']['coordinates'];
```

**Avantages**

* L'API Adresse répond à notre besoin à l'instant T.
* Les coordonnées géographiques sont renvoyées selon la projection standard WSG-84 (EPSG:4326).
* Les sources de données suggèrent une bonne couverture de l'ensemble du territoire français.
* La BAN fait aujourd'hui référence. Elle est la "seule base nationale de référence sur l'adresse à faire partie du socle de souveraineté de l'Etat" ([source](https://doc.adresse.data.gouv.fr/)). [La BAN est portée par l'IGN depuis mars 2022](https://www.numerique.gouv.fr/espace-presse/la-base-adresse-nationale-ban-franchit-de-nouvelles-etapes-en-poursuivant-son-action-au-sein-de-lign/) : "L'IGN cesse les mises à jour de ses outils et bases historiques [...] et implique ses équipes dans l'administration de la BAN."
* L'API Adresse est [bien documentée](https://adresse.data.gouv.fr/api-doc/adresse).

**Inconvénients**

* RAS

### Option 2 - Géocodeur alternatif

Etalab cite des [géocodeurs alternatifs](https://guides.etalab.gouv.fr/apis-geo/1-api-adresse.html#geocodeurs-alternatifs) (open source ou service public tel que l'IGN), avec cette note :

> Leurs principaux intérêts sont de pouvoir chercher des POIs, par exemple un centre commercial ou une enseigne ainsi que de marcher sur des données internationales, contrairement à [l'API Adresse].

À l'instant T, le besoin de DiaLog n'incluait ni la rechercher par POI, ni l'accès à des données internationales. Il ne semblait donc pas justifié d'utiliser un géocodeur alternatif.

A fortiori, les géocodeurs propriétaires (exemple : _Geocoding API_ de Google) n'ont pas été envisagés.

### Option 3 - API Géocodage 2.0 de l'IGN

L'API Géocodage 2.0 de l'IGN est une API Web (HTTP GET renvoyant du XML) qui permet de rechercher des éléments géographiques à partir d'une adresse textuelle.

Cette API a une interface similaire à l'API Adresse. En fait, la version 2.0 découle probablement de la démarche de l'IGN de prendre la BAN comme référence.


**Avantages**

* Elle répond au besoin à l'instant T, à savoir convertir une adresse textuelle en coordonnées géographiques (latitude, longitude).
* Elle est alimentée par diverses sources, dont : BD TOPO®, ... (TODO)

**Inconvénients**

* La disponibilité du service est incertaine. (TODO)
* La documentation peut être moins détaillée que des alternatives tierces (voir Option 3). Nos expérimentations ont cependant montré qu'elle était suffisante pour nos besoins.
* L'utiliser alors que l'API Adresse suffit dérogerait aux [pratiques habituelles Etalab en matière de géocodage](https://guides.etalab.gouv.fr/apis-geo/1-api-adresse.html). Cela peut impacter la reprise du projet par d'autres personnes (effet de surprise).

## Références

TODO

* Lien de la documentation API Géocodage 2.0
