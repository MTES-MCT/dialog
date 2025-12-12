# API DiaLog

> Cette API est en **bêta** et en cours de construction. Vos retours nous permettent de la fiabiliser et d’ajuster le schéma si nécessaire.

Cette page décrit l’API publique de DiaLog, qui expose des endpoints d’écriture (création d’arrêtés) et de lecture (exports). Elle s’adresse aux organismes et collectivités souhaitant intégrer des arrêtés de circulation dans DiaLog via un échange machine-à-machine.

## Authentification

L’API utilise une authentification par en-têtes HTTP spécifiques. Ces identifiants sont délivrés par l’équipe DiaLog.

- X-Client-Id: identifiant client
- X-Client-Secret: secret client

L’authentification est requise pour les endpoints suivants :

- POST `/api/regulations` (création d’un arrêté)
- PUT `/api/regulations/{uuid}/publish` (publication d’un arrêté existant)
- GET `/api/organization/identifiers` (récupération des identifiants déjà utilisés par votre organisation)

Les exports (lecture) via GET (`/api/regulations.xml`, `/api/regulations/cifs.xml` et `/api/stats`) restent publics et ne nécessitent pas d'authentification.

## Documentation OpenAPI

- Documentation JSON: `/api/doc.json`
- Client intégré (Swagger UI): `/api/doc`

## Endpoints

### Créer un arrêté

- Méthode: POST
- URL: `/api/regulations`
- Authentification requise: oui (en-têtes `X-Client-Id`, `X-Client-Secret`)
- Corps: JSON
- Réponses: 201, 400, 401, 422

#### Schéma du corps JSON

Le schéma exact (champs, contraintes, champs requis) est susceptible d’évoluer. Veuillez vous référer à la documentation OpenAPI disponible sur `/api/doc` pour la définition à jour.

Ci-dessous, un récapitulatif des champs acceptés aujourd’hui:

- `identifier` (string, max 60) — identifiant d’arrêté, ex: `F2025/001`
- `category` (string) — catégorie de l’arrêté: `temporaryRegulation` | `permanentRegulation`
- `subject` (string, nullable) — sujet: `roadMaintenance` | `incident` | `event` | `winterMaintenance` | `other`
- `otherCategoryText` (string, nullable, max 100)
- `title` (string, max 255)
- `measures` (array<object>, nullable)
  - `type` (string) — enum: `noEntry` | `speedLimitation` | `parkingProhibited`
  - `maxSpeed` (integer, nullable) — requis uniquement si `type = speedLimitation`
  - `createdAt` (string, date-time ISO, nullable)
  - `vehicleSet` (object, nullable)
    - `allVehicles` (boolean, nullable)
    - `restrictedTypes` (string[], nullable) — valeurs autorisées: voir enum ci-dessous
    - `exemptedTypes` (string[], nullable) — valeurs autorisées: voir enum ci-dessous
    - `otherRestrictedTypeText` (string, nullable)
    - `otherExemptedTypeText` (string, nullable)
    - `heavyweightMaxWeight` (number, nullable) - valeurs autorisées : `3.5`, `7.5`, `12`, `19`, `26`, `32`, `44`
    - `maxWidth` (number, nullable)
    - `maxLength` (number, nullable)
    - `maxHeight` (number, nullable)
    - `critairTypes` (string[], nullable) — valeurs: `critair0` (ex. VE), `critair1`, `critair2`, `critair3`, `critair4`, `critair5`
- `periods` (array<object>, nullable)
  - `startDate` (string, date-time ISO) — **obligatoire**
  - `startTime` (string, date-time ISO, nullable) — requis si `isPermanent = false`
  - `endDate` (string, date-time ISO, nullable) — requis si `isPermanent = false`
  - `endTime` (string, date-time ISO, nullable) — requis si `isPermanent = false`
  - `recurrenceType` (string) — enum: `everyDay` | `certainDays` — **obligatoire**
  - `isPermanent` (boolean, nullable)
  - `dailyRange` (object, nullable)
    - `applicableDays` (string[], nullable) — enum: `monday` | `tuesday` | `wednesday` | `thursday` | `friday` | `saturday` | `sunday`
  - `timeSlots` (array<object>, nullable)
    - `startTime` (string, date-time ISO, nullable)
    - `endTime` (string, date-time ISO, nullable)
  - `locations` (array<object>, nullable)
    - `roadType` (string) — enum: `lane` | `departmentalRoad` | `nationalRoad` | `rawGeoJSON`
    - `namedStreet` (object, nullable) — utilisé avec `roadType = lane`
      - `cityCode` (string, nullable) — code INSEE de la commune
      - `cityLabel`, `roadName` (string, nullable)
      - `fromPointType` (string, nullable) - enum : `houseNumber` | `intersection`
      - `fromHouseNumber`, `fromRoadName` (string, nullable)
      - `toPointType` (string, nullable) - enum : `houseNumber` | `intersection`
      - `toHouseNumber`, `toRoadName` (string, nullable)
      - `direction` (string, nullable) — enum: `BOTH` | `A_TO_B` | `B_TO_A`
    - `departmentalRoad` (object, nullable) — utilisé avec `roadType = departmentalRoad`
      - `administrator`, `roadNumber`, `fromDepartmentCode`, `fromPointNumber`, `toDepartmentCode`, `toPointNumber` (string, nullable)
      - `fromAbscissa`, `toAbscissa` (integer, nullable)
      - `fromSide`, `toSide` (string, nullable)
      - `direction` (string, nullable) — enum: `BOTH` | `A_TO_B` | `B_TO_A`
    - `nationalRoad` (object, nullable) — utilisé avec `roadType = nationalRoad`
      - mêmes champs que `departmentalRoad`
    - `rawGeoJSON` (object, nullable) — utilisé avec `roadType = rawGeoJSON`
      - `label` (string, nullable)
      - `geometry` (string GeoJSON, nullable)

##### Enums détaillés

- `measures[*].type` (MeasureTypeEnum): `noEntry`, `speedLimitation`, `parkingProhibited`
- `locations[*].roadType` (RoadTypeEnum): `lane`, `departmentalRoad`, `nationalRoad`, `rawGeoJSON`
- `namedStreet.direction`, `departmentalRoad.direction`, `nationalRoad.direction` (DirectionEnum): `BOTH`, `A_TO_B`, `B_TO_A`
- `periods[*].recurrenceType`, `dailyRange.recurrenceType` (PeriodRecurrenceTypeEnum): `everyDay`, `certainDays`
- `dailyRange.applicableDays` (ApplicableDayEnum): `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`
- `vehicleSet.restrictedTypes` / `vehicleSet.exemptedTypes` (VehicleTypeEnum, sous-ensembles):
  - Restreints possibles: `heavyGoodsVehicle`, `dimensions`, `critair`, `hazardousMaterials`, `other`
  - Exemptés possibles: `commercial`, `emergencyServices`, `bicycle`, `pedestrians`, `taxi`, `carSharing`, `roadMaintenanceOrConstruction`, `cityLogistics`, `other`
- `vehicleSet.critairTypes` (CritairEnum): `critair0` (ex. VE), `critair1`, `critair2`, `critair3`, `critair4`, `critair5`

#### Exemple de requête (complet)

```json
{
  "identifier": "F2025/001",
  "category": "temporaryRegulation",
  "subject": "roadMaintenance",
  "title": "Travaux",
  "measures": [
    {
      "type": "speedLimitation",
      "maxSpeed": 30,
      "createdAt": "2025-10-09T08:00:00Z",
      "vehicleSet": {
        "allVehicles": false,
        "restrictedTypes": ["heavyGoodsVehicle","dimensions","critair"],
        "exemptedTypes": ["emergencyServices","roadMaintenanceOrConstruction"],
        "heavyweightMaxWeight": 3.5,
        "maxWidth": 2.1,
        "maxLength": 7.5,
        "maxHeight": 3.0,
        "critairTypes": ["critair4","critair5"]
      },
      "periods": [
        {
          "startDate": "2025-10-10T00:00:00Z",
          "startTime": "2025-10-10T08:00:00Z",
          "endDate": "2025-10-20T00:00:00Z",
          "endTime": "2025-10-20T18:00:00Z",
          "recurrenceType": "certainDays",
          "isPermanent": false,
          "dailyRange": {
            "recurrenceType": "certainDays",
            "applicableDays": ["monday","tuesday","wednesday"]
          },
          "timeSlots": [
            { "startTime": "2025-10-10T08:00:00Z", "endTime": "2025-10-10T12:00:00Z" },
            { "startTime": "2025-10-10T14:00:00Z", "endTime": "2025-10-10T18:00:00Z" }
          ]
        }
      ],
      "locations": [
        {
          "roadType": "lane",
          "namedStreet": {
          "cityCode": "75056", // code INSEE
            "cityLabel": "Paris",
            "roadName": "Rue Exemple",
            "fromPointType": "houseNumber",
            "fromHouseNumber": "10",
            "toPointType": "houseNumber",
            "toHouseNumber": "20",
            "direction": "BOTH"
          }
        }
      ]
    }
  ]
}
```

#### Exemple de requête

Voir `/api/doc` pour le schéma à jour du corps JSON. Exemple d’appel générique:

```bash
curl -X POST \
  'https://dialog.beta.gouv.fr/api/regulations' \
  -H 'Content-Type: application/json' \
  -H 'X-Client-Id: VOTRE_CLIENT_ID' \
  -H 'X-Client-Secret: VOTRE_CLIENT_SECRET' \
  --data-binary @payload.json
```

#### Réponses

- 201 Création réussie

```json
{
  "message": "Regulation 123e4567-e89b-12d3-a456-426614174000 created"
}
```

- 400 Erreur métier (géocodage / périmètre géographique)

```json
{
  "status": 400,
  "detail": "Le géocodage de la voie a échoué."
}
```

- 401 Non authentifié / identifiants invalides

```json
{
  "message": "Unauthorized"
}
```

- 422 Erreur de validation

Lorsque des erreurs de validation surviennent, la réponse a la structure suivante:

```json
{
  "status": 422,
  "detail": "Validation failed",
  "violations": [
    {
      "propertyPath": "title",
      "title": "Cette valeur ne doit pas être vide.",
      "parameters": {}
    }
  ]
}
```

Remarques:
- Les erreurs métier (code 400) incluent notamment: échec de géocodage de voie, abscisse hors plage, échec de géocodage de route numérotée, impossibilité d'intervention de l'organisation sur la géométrie.
- Les erreurs de validation (code 422) concernent les contraintes de format et de cohérence des données.

### Publier un arrêté existant

- Méthode: PUT
- URL: `/api/regulations/{uuid}/publish`
- Authentification requise: oui (en-têtes `X-Client-Id`, `X-Client-Secret`)
- Corps: aucun
- Réponses: 200, 400, 401, 403, 404

Ce endpoint publie un arrêté déjà créé (statut `draft`). Il applique les mêmes règles métier que l’interface: au moins une mesure, données complètes, etc.

#### Exemples de réponses

- 200 OK

```json
{
  "uuid": "e413a47e-5928-4353-a8b2-8b7dda27f9a5",
  "status": "published"
}
```

- 400 Publication impossible (ex. aucune mesure)

```json
{
  "status": 400,
  "detail": "L'arrêté ne peut pas être publié."
}
```

- 403 Organisation non autorisée / arrêté appartenant à une autre organisation

```json
{
  "status": 403,
  "detail": "Forbidden"
}
```

- 404 Arrêté inexistant

```json
{
  "status": 404,
  "detail": "Not Found"
}
```

Remarques :
- Seuls les arrêtés appartenant à l’organisation liée aux identifiants API peuvent être publiés.
- Si la publication échoue, corrigez les données via l’interface avant de relancer l’appel.

### Lister les identifiants existants de son organisation

- Méthode: GET
- URL: `/api/organization/identifiers`
- Authentification requise: oui (en-têtes `X-Client-Id`, `X-Client-Secret`)
- Réponse: JSON contenant la liste des identifiants déjà utilisés par votre organisation, triés par ordre alphabétique.

#### Exemple de réponse

```json
{
  "identifiers": [
    "117374#24-A-0473",
    "F/CIFS/2023",
    "FO1/2023"
  ]
}
```

Cet endpoint est utile pour vérifier rapidement la disponibilité d’un identifiant avant la création d’un nouvel arrêté. Seuls les arrêtés appartenant à l’organisation associée à vos identifiants API sont renvoyés.

## Support

Pour obtenir des identifiants d’accès ou signaler un problème, contactez l’équipe DiaLog.

### Export DATEX II

- Méthode: GET
- URL: `/api/regulations.xml`
- Authentification requise: non
- Réponse: XML (`Content-Type: text/xml; charset=UTF-8`)

#### Paramètres de requête (filtres)

- `includePermanent` (boolean, défaut `true`): inclut les arrêtés permanents.
- `includeTemporary` (boolean, défaut `true`): inclut les arrêtés temporaires.
- `includeExpired` (boolean, défaut `false`): inclut les arrêtés temporaires expirés.

Règles d’interprétation:
- Si `includePermanent=false`, les permanents sont exclus.
- Si `includeTemporary=false`, les temporaires sont exclus.
- Si les deux sont `false`, la réponse est vide.
- Lorsque `includeExpired=false`, seuls les temporaires dont la date de fin n’est pas dépassée sont renvoyés (les permanents restent inclus selon `includePermanent`).

#### Exemple de requête

```bash
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations.xml' -H 'Accept: application/xml'
```

Exemples avec filtres:

```bash
# Uniquement permanents
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations.xml?includeTemporary=false' -H 'Accept: application/xml'

# Uniquement temporaires non expirés
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations.xml?includePermanent=false&includeTemporary=true&includeExpired=false' -H 'Accept: application/xml'

# Tous les temporaires, y compris expirés (et sans permanents)
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations.xml?includePermanent=false&includeTemporary=true&includeExpired=true' -H 'Accept: application/xml'
```

#### Détails

- Le flux suit le standard DATEX II (schémas disponibles dans `docs/spec/datex2/`).
- Le document XML contient l’ensemble des arrêtés publiés, prêts à être réutilisés.

### Export CIFS (Waze)

- Méthode: GET
- URL: `/api/regulations/cifs.xml`
- Authentification requise: non
- Réponse: XML (`Content-Type: text/xml; charset=UTF-8`)

#### Exemple de requête

```bash
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations/cifs.xml' -H 'Accept: application/xml'
```

#### Limitations

- En raison des limites du format propriétaire CIFS (Waze), seules les interdictions de circulation temporaires s'appliquant à tous les véhicules sont exposées. Les autres cas (ex. zone 30, restrictions poids lourds, permanentes, etc.) ne sont pas inclus.

### Géométries des organisations (statistiques)

- Méthode: GET
- URL: `/api/stats`
- Authentification requise: non
- Réponse: JSON (`Content-Type: application/json`)

#### Description

Cet endpoint retourne les géométries de l'ensemble des organisations enregistrées dans DiaLog au format GeoJSON. Il est principalement utilisé pour afficher la couverture géographique des organisations sur une carte (page statistiques).

#### Format de réponse

Le format respecte la spécification GeoJSON (RFC 7946). Les géométries peuvent être de différents types selon les organisations:
- `Polygon`: pour les organisations couvrant une zone géographique (commune, département, etc.)
- `MultiPolygon`: pour les organisations avec plusieurs zones non contiguës

#### Exemple de requête

```bash
curl -X GET 'https://dialog.beta.gouv.fr/api/stats' -H 'Accept: application/json'
```

#### Exemple de réponse

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [
          [
            [2.224122, 48.841620],
            [2.469920, 48.815573],
            [2.469920, 48.902158],
            [2.224122, 48.902158],
            [2.224122, 48.841620]
          ]
        ]
      },
      "properties": {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Ville de Paris",
        "code": "75056",
        "codeType": "insee",
        "departmentName": "Paris",
        "departmentCode": "75"
      }
    },
    {
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [
          [
            [2.373620, 48.892722],
            [2.404251, 48.892722],
            [2.404251, 48.909017],
            [2.373620, 48.909017],
            [2.373620, 48.892722]
          ]
        ]
      },
      "properties": {
        "uuid": "234e5678-e89b-12d3-a456-426614174001",
        "name": "Ville de Saint-Denis",
        "code": "93066",
        "codeType": "insee",
        "departmentName": "Seine-Saint-Denis",
        "departmentCode": "93"
      }
    }
  ]
}
```

#### Propriétés des features

Chaque feature contient les propriétés suivantes:

- `uuid` (string): Identifiant unique de l'organisation
- `name` (string): Nom de l'organisation
- `code` (string, nullable): Code de l'organisation (ex. code INSEE)
- `codeType` (string, nullable): Type de code (`insee`, `siren`, etc.)
- `departmentName` (string, nullable): Nom du département
- `departmentCode` (string, nullable): Code du département

#### Notes

- Seules les organisations possédant une géométrie valide et non vide sont incluses dans la réponse.
- Les coordonnées utilisent le système de référence WGS84 (EPSG:4326).
- Cet endpoint est public et ne nécessite pas d'authentification pour faciliter l'affichage des statistiques.
