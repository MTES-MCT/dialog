# Signalements adresse vers l'IGN (Espace collaboratif / GCMS)

Les signalements d'adresses non reconnues par les utilisateurs de DiaLog sont envoyés à l'[Espace collaboratif IGN](https://espacecollaboratif.ign.fr) (API GCMS). DiaLog enregistre l'identifiant et le statut renvoyés par l'API, peut recevoir les mises à jour de statut via un webhook et notifier l'équipe support par email.

## Contexte

Lorsqu'un utilisateur signale une adresse ou une voie qu'il n'a pas trouvée (formulaire « Signaler un problème avec une adresse »), DiaLog :

1. Enregistre le signalement en base et envoie un email à l'équipe support.
2. Envoie le signalement à l'API IGN (géométrie + commentaire) si une géométrie est disponible (centroïde de la voie ou de l'organisation).
3. Enregistre l'identifiant et le statut renvoyés par l'IGN (affichés dans le backoffice).

Les signalements sont visibles côté IGN sur [Espace collaboratif – Liste des signalements](https://espacecollaboratif.ign.fr/georem/). Chaque signalement DiaLog peut être ouvert via le lien « ID signalement IGN » dans l'admin (lien vers `https://espacecollaboratif.ign.fr/georem/{id}`).

## Configuration

Variables d'environnement :

| Variable | Description |
|----------|-------------|
| `API_IGN_REPORT_BASE_URL` | URL de base de l'API (ex. `https://espacecollaboratif.ign.fr`) |
| `API_IGN_REPORT_AUTH` | Identifiants au format `user:password` |
| `IGN_REPORT_STATUS` | Statut initial du signalement envoyé à l'IGN (ex. `submit`, `test`) |
| `IGN_WEBHOOK_SECRET` | Secret partagé avec l'IGN pour authentifier les appels du webhook. Laisser vide désactive l'acceptation des mises à jour. |

## Envoi vers l'IGN (POST)

- **Endpoint côté IGN** : `POST {API_IGN_REPORT_BASE_URL}/gcms/api/reports`
- Le commentaire est préfixé par `[DiaLog]`. La géométrie est envoyée en WKT (point).
- Si la réponse est 2xx et contient un body avec `id` (et optionnellement `status`), DiaLog enregistre ces valeurs sur le signalement (`ign_report_id`, `ign_report_status`, `ign_status_updated_at`).

## Récupération du statut (GET)

- **Endpoint côté IGN** : `GET {API_IGN_REPORT_BASE_URL}/gcms/api/reports/{id}`
- Le client `IgnReportClient::getReportStatus(ignReportId)` permet d'interroger l'API pour rafraîchir le statut côté DiaLog (non utilisé automatiquement ; utile pour un script ou une action manuelle).

## Webhook : mise à jour du statut par l'IGN

L'IGN peut notifier DiaLog lorsqu'un signalement change de statut en appelant l'endpoint suivant.

### Endpoint DiaLog

- **Méthode** : POST  
- **URL** : `/api/webhooks/ign-report-status`  
- **Authentification** : en-tête `X-IGN-Webhook-Secret` doit contenir la valeur de `IGN_WEBHOOK_SECRET`.

### Corps de la requête (JSON)

| Champ | Description |
|-------|-------------|
| `reportId` ou `id` | Identifiant du signalement côté IGN (string) |
| `status` | Nouveau statut (string) |

Exemple :

```json
{
  "reportId": "1109223",
  "status": "treated"
}
```

### Comportement

- En cas de succès (200) : le signalement DiaLog correspondant (trouvé par `ign_report_id`) est mis à jour avec le statut et la date de dernière mise à jour ; un email est envoyé à l'équipe support (`EMAIL_SUPPORT`) pour notifier le changement.
- Réponses d'erreur : 400 (JSON invalide ou champs manquants), 401 (secret absent ou incorrect), 404 (signalement non trouvé).

### Documentation API publique

Cet endpoint est également décrit dans la [documentation API DiaLog](../public/api.md#webhook-ign-mise-à-jour-du-statut-des-signalements).

## Backoffice

Dans l'admin (« Signalements adresse »), les champs suivants sont affichés :

- **ID signalement IGN** : lien cliquable vers la fiche du signalement sur l'Espace collaboratif IGN.
- **Statut IGN** : dernier statut connu.
- **Dernière MAJ statut IGN** : date de la dernière mise à jour du statut.

## Voir aussi

- [Services externes](./services.md) – vue d’ensemble des APIs utilisées par DiaLog.
