# Redis

Nous utilisons Redis pour stocker les sessions utilisateurs et gérer les messages en asynchrone.

## Connexion

Pour se connecter au client Redis, utilisez la commande :

```bash
make redisshell
```

## Redis commander

Redis Commander fournit une interface web pour visualiser et gérer le contenu de Redis et notamment les messages.

URL : http://dialog.localhost:8081

## Commandes utiles

```bash
# Voir le status des workers
make supervisor_status
````

```bash
# Redémarrer les workers
make supervisor_restart
```
