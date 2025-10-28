# API DiaLog

Cette page décrit l’API publique de DiaLog, qui expose des endpoints d’écriture (création d’arrêtés) et de lecture (exports). Elle s’adresse aux organismes et collectivités souhaitant intégrer des arrêtés de circulation dans DiaLog via un échange machine-à-machine.

## Authentification

L’API utilise une authentification par en-têtes HTTP spécifiques. Ces identifiants sont délivrés par l’équipe DiaLog.

- X-Client-Id: identifiant client
- X-Client-Secret: secret client

L’authentification est requise UNIQUEMENT pour l’endpoint POST `/api/regulations` (écriture).

Les exports (lecture) via GET (`/api/regulations.xml` et `/api/regulations/cifs.xml`) sont publics et ne nécessitent pas d’authentification.

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
    - `heavyweightMaxWeight` (number, nullable)
    - `maxWidth` (number, nullable)
    - `maxLength` (number, nullable)
    - `maxHeight` (number, nullable)
    - `critairTypes` (string[], nullable) — valeurs: `critair2`, `critair3`, `critair4`, `critair5`
  - `periods` (array<object>, nullable)
    - `startDate` (string, date-time ISO, nullable)
    - `startTime` (string, date-time ISO, nullable)
    - `endDate` (string, date-time ISO, nullable)
    - `endTime` (string, date-time ISO, nullable)
    - `recurrenceType` (string, nullable) — enum: `everyDay` | `certainDays`
    - `isPermanent` (boolean, nullable)
    - `dailyRange` (object, nullable)
      - `recurrenceType` (string, nullable) — enum comme ci-dessus
      - `applicableDays` (string[], nullable) — enum: `monday` | `tuesday` | `wednesday` | `thursday` | `friday` | `saturday` | `sunday`
    - `timeSlots` (array<object>, nullable)
      - `startTime` (string, date-time ISO, nullable)
      - `endTime` (string, date-time ISO, nullable)
  - `locations` (array<object>, nullable)
    - `roadType` (string) — enum: `lane` | `departmentalRoad` | `nationalRoad` | `rawGeoJSON`
    - `namedStreet` (object, nullable) — utilisé avec `roadType = lane`
      - `cityCode` (string, nullable) — code INSEE de la commune
      - `cityLabel`, `roadName` (string, nullable)
      - `fromPointType`, `fromHouseNumber`, `fromRoadName` (string, nullable)
      - `toPointType`, `toHouseNumber`, `toRoadName` (string, nullable)
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
  - Exemptés possibles: `commercial`, `emergencyServices`, `bicycle`, `pedestrians`, `taxi`, `carSharing`, `roadMaintenanceOrConstruction`, `other`
- `vehicleSet.critairTypes` (CritairEnum): `critair2`, `critair3`, `critair4`, `critair5`

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

## Support

Pour obtenir des identifiants d’accès ou signaler un problème, contactez l’équipe DiaLog.

### Export DATEX II

- Méthode: GET
- URL: `/api/regulations.xml`
- Authentification requise: non
- Réponse: XML (`Content-Type: text/xml; charset=UTF-8`)

#### Exemple de requête

```bash
curl -X GET 'https://dialog.beta.gouv.fr/api/regulations.xml' -H 'Accept: application/xml'
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

- En raison des limites du format propriétaire CIFS (Waze), seules les interdictions de circulation temporaires s’appliquant à tous les véhicules sont exposées. Les autres cas (ex. zone 30, restrictions poids lourds, permanentes, etc.) ne sont pas inclus.
