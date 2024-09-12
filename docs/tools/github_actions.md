# GitHub Actions

GitHub Actions est utilisé dans ce projet pour la CI, mais aussi pour l'exécution automatique des intégrations de données.

## Clés SSH pour les imports automatiques

GitHub Actions a besoin d'un accès SSH à Scalingo pour accéder à la base de données de façon sécurisée.

Pour cela des clés SSH ont été générées comme suit :

```bash
ssh-keygen -t ed25519 -q -N "" -f ~/.ssh/id_dialog_gh_scalingo
```

La clé publique `~/.ssh/id_dialog_gh_scalingo.pub` ainsi générée a été enregistrée sur Scalingo dans la section [Mes clés SSH](https://dashboard.scalingo.com/account/keys) du compte Scalingo professionnel de @florimondmanca.

> 💡 Pour renouveler les clés, ou en cas de perte, de nouvelles clés peuvent être régénérées en utilisant la méthode ci-dessus, puis rattachées au compte de toute personne ayant un accès "Collaborator" sur l'app Scalingo `dialog`.

La clé privée a été ajoutée comme secret `GH_SCALINGO_SSH_PRIVATE_KEY` au dépôt GitHub et est utilisée par la GitHub Action.

### Accès de GitHub Actions à la base de données sur Scalingo

L'accès à la base de données lors d'un import se fait via un [tunnel chiffré Scalingo](https://doc.scalingo.com/platform/databases/access#encrypted-tunnel).

Le workflow de l'intégration doit faire en sorte qu'une `DATABASE_URL` appropriée soit configurée pour l'application.

Pour obtenir automatiquement cette URL pour l'application `APP`, exécutez :

```bash
./tools/scalingodbtunnel APP --host-url
# Exemple pour la prod :
./tools/scalingodbtunnel dialog --host-url
```

Et recopiez l'URL qui s'affiche.

> Cette commande nécessite le CLI Scalingo, voir [Utiliser une DB Scalingo en local](./db.md#utiliser-une-db-scalingo-en-local).

Sinon il vous faut récupérer la `DATABASE_URL` dans l'interface web Scalingo.
