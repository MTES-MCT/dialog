# Signalements adresse vers l'IGN (Espace collaboratif / GCMS)

Les signalements d'adresses non reconnues par les utilisateurs de DiaLog sont envoyés à l'[Espace collaboratif IGN](https://espacecollaboratif.ign.fr) (API GCMS). DiaLog enregistre l'identifiant et le statut renvoyés par l'API, synchronise quotidiennement les statuts via un cron et notifie l'équipe support par email en cas de changement.

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

## Envoi vers l'IGN (POST)

- **Endpoint côté IGN** : `POST {API_IGN_REPORT_BASE_URL}/gcms/api/reports`
- Le commentaire est préfixé par `[DiaLog]`. La géométrie est envoyée en WKT (point).
- Si la réponse est 2xx et contient un body avec `id` (et optionnellement `status`), DiaLog enregistre ces valeurs sur le signalement (`ign_report_id`, `ign_report_status`, `ign_status_updated_at`).

## Synchronisation des statuts (cron)

La commande `app:ign:sync-report-statuses` interroge l'API IGN pour chaque signalement ayant un `ign_report_id` et met à jour le statut si celui-ci a changé. Un email est envoyé à l'équipe support (`EMAIL_SUPPORT`) pour chaque changement de statut.

```bash
php bin/console app:ign:sync-report-statuses
```

Cette commande est prévue pour être exécutée quotidiennement via un cron ou un scheduler.

### Détail du fonctionnement

1. Récupère tous les signalements ayant un `ign_report_id` en base.
2. Pour chacun, interroge `GET {API_IGN_REPORT_BASE_URL}/gcms/api/reports/{id}`.
3. Si le statut a changé : met à jour `ign_report_status` et `ign_status_updated_at`, envoie un email de notification.
4. Si l'API retourne `null` (erreur réseau, 404…) ou le même statut : aucune action.

## Backoffice

Dans l'admin (« Signalements adresse »), les champs suivants sont affichés :

- **ID signalement IGN** : lien cliquable vers la fiche du signalement sur l'Espace collaboratif IGN.
- **Statut IGN** : dernier statut connu.
- **Dernière MAJ statut IGN** : date de la dernière mise à jour du statut.

## Voir aussi

- [Services externes](./services.md) – vue d'ensemble des APIs utilisées par DiaLog.
