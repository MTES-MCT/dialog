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
- Réponses: 201, 401, 422

#### Schéma du corps JSON

Le schéma exact (champs, contraintes, champs requis) est susceptible d’évoluer. Veuillez vous référer à la documentation OpenAPI disponible sur `/api/doc` pour la définition à jour.

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
