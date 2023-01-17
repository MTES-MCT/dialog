# 005 - Choix d'un service de géocodage

* Date : 2023-01-17
* Personnes impliquées : Florimond Manca (auteur), Mathieu Marchois
* Statut : BROUILLON <!-- [BROUILLON|ACCEPTÉ|REJETÉ|DÉPRÉCIÉ] -->

## Contexte

Dans le cadre du MVP, il a été décidé de modéliser la localisation où s'applique une réglementation par une portion de rue. La portion de rue est représentée par : code postal, ville, rue, numéro de début, numéro de fin. (Voir [#43](https://github.com/MTES-MCT/dialog/issues/43) pour plus d'informations.)

Étant données deux points de coordonnées géographiques, le format de données DATEX II (voir [ADR-001](./001_exchangeformat.md)) permet de représenter une portion linéaire de route par l'entité `LinearWithinLinearElement`.

L'approche fut donc de transformer l'adresse de début (adresse + numéro de début) et l'adresse de fin (adresse + numéro de fin) en coordonnées géographiques en vue de l'intégrer à l'export DATEX II et _in fine_ les mettre à disposition des GPS.

Cette opération _adresse -> coordonnées géographiques_ s'appelle le **géocodage** (_geocoding_ en anglais).

Il s'agissait donc de décider quel service de géocodage utiliser.

## Décision

Nous avons décidé d'utiliser l'[API Géocodage 2.0]() (TODO) de l'[IGN](https://www.ign.fr). Cette API fait partie des [Géoservices](https://geoservices.ign.fr) de l'IGN.

## Conséquences

<!--
Décrire ici ce qui devra être mis en place suite à la décision.

Exemple :

* Les décisions importantes d'architecture seront archivées dans le dossier `adr`.
* Le fichier d'ADR sera nommé `nnn_titre.md`.
* L'ADR sera créée via une PR dont le titre de commit commence par `ADR:`.
-->

## Options envisagées

### Option 1 - API Géocodage 2.0 de l'IGN

**Description**

L'API Géocodage 2.0 de l'IGN est une API Web (XML ou HTTP GET) qui permet de rechercher des éléments géographiques à partir d'une adresse textuelle.

**Utilisation**

En tant qu'API de recherche, l'API peut renvoyer plusieurs résultats, il faut donc appliquer les paramètres de recherche appropriés.

En l'occurrence, nous obtenons des résultats probants avec les paramètres suivants:

* `q=...` - Adresse textuelle
* `type=housenumber` : ne renvoyer que les éléments géographiques correspondant à des numéros de rue
* `limit=1` : renvoyer un seul résultat (le plus pertinent)

Exemple de requête et de réponse pour l'adresse textuelle "3 Rue des tournesols 82000 Montauban" :

```http
GET https://wxs.ign.fr/calcul/geoportail/geocodage/rest/0.1/search?q=3%20Rue%20des%20tournesols%2082000%20Montauban&type=housenumber&limit=1 HTTP/1.1
Accept: application/json
```

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "label": "3 Rue des Tournesols 82000 Montauban",
        "score": 0.9698190909090907,
        "housenumber": "3",
        "id": "82121_7724_00003",
        "name": "3 Rue des Tournesols",
        "postcode": "82000",
        "citycode": "82121",
        "x": 570724.97,
        "y": 6329107.22,
        "city": "Montauban",
        "context": "82, Tarn-et-Garonne, Occitanie",
        "type": "housenumber",
        "importance": 0.66801,
        "street": "Rue des Tournesols",
        "_score": 0.9698190909090907,
        "_type": "ban"
      },
      "geometry": {
        "type": "Point",
        "coordinates": [
          1.386715,
          44.049081
        ]
      }
    }
  ]
}
```

Les coordonnées (longitude, latitude) peuvent alors être obtenues par :

```php
$lonLat = $data['features'][0]['geometry']['coordinates'];
```

**Avantages**

* Elle répond au besoin à l'instant T, à savoir convertir une adresse textuelle en coordonnées géographiques (latitude, longitude).
* Elle est alimentée par diverses sources, dont : BD TOPO®, ... (TODO)

**Inconvénients**

* La disponibilité du service est incertaine. (TODO)
* La documentation peut être moins détaillée que des alternatives tierces (voir Option 3). Nos expérimentations ont cependant montré qu'elle était suffisante pour nos besoins.

### Option 2 - API Géocodage historique de l'IGN

TODO

### Option 3 - API de géocodage tierce

Exemples : _Geocoding API_ de Google, de Mapbox, d'ArcGIS, d'OpenStreetMap...

Cette option n'a pas été envisagée car la priorité a été donnée aux outils du service public dès lors qu'ils pouvaient répondre au besoin.

## Références

TODO

* Lien de la documentation API Géocodage 2.0
