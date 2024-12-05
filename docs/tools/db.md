# Base de données

## Connexion

Pour se connecter au client PostgreSQL, utilisez la commande :

```bash
make dbshell
```

## Migrations

Lorsque vous effectuez des modifications sur les entités doctrine ainsi que sur les fichiers de mapping, vous devez générer une migration pour qu'elle soit versionnée.

Pour générer une migration, utilisez la commande :

```bash
make dbmigration
```

Une fois la migration générée, il faut l'executer. Pour ce faire il existe la commande suivante qui va prendre l'ensemble des migrations non jouées et les executer une à une.

```bash
make dbmigrate
```

## pgAdmin4

Pour pouvoir accéder à pgAdmin, il faut se rendre sur l'URL http://localhost:5050 et se connecter avec le compte définit dans le fichier [`docker-compose.yml`](../docker-compose.yml).

Une configuration est nécéssaire à la première connection pour relier pgAdmin à notre base de données. Pour ce faire :
- Cliquez sur "Add New Server"
- Dans l'onglet `General`, remplissez les valeurs suivantes :
    - **Name**: dialog
- Dans l'onglet `Connection`, remplissez les valeurs suivantes avec les informations définies dans le fichier [`docker-compose.yml`](../docker-compose.yml) :
    - **Host name** : database
    - **Port** : 5432
    - **Maintenance database** : dialog
    - **Username** : dialog
    - **Password** : dialog

Et c'est tout ! Vous aurez maintenant accès à l'interface graphique pour gérer la base de données.

## Base de données de branche

Pour développer sur une branche contenant de nouvelles migrations, il peut être utile d'utiliser une DB dédiée afin de ne pas affecter votre DB de développement principale.

Pour cela utilisez `make createdb` :

```bash
make createdb NAME=_ma_feature # Noter le "_" devant
```

Une base de données `dialog_ma_feature` sera créée à partir de la base `dialog`.

Pour l'utiliser, il faut mettre à jour `DATABASE_URL` dans les fichiers `.env.local` et `.env.test.local`. Vous pouvez le faire en une commande avec `make usedb` :

```bash
make usedb NAME=ma_feature
```

Pour retourner à la DB principale, utilisez `make usdb` (sans paramètre `NAME`).

Quand vous n'avez plus besoin de la DB, utilisez `make dropdb` :

```bash
make dropdb NAME=ma_feature
```

## Utiliser une DB Scalingo en local

Vous pouvez utiliser en local une base de données hébergée sur Scalingo (staging, PR...) à l'aide d'un utilitaire.

**Prérequis**

* La commande `scalingo` (Scalingo CLI) doit être disponible. Vérifiez avec : `$ which scalingo`. Pour l'installer, voir [Command Line Interface (CLI)](https://doc.scalingo.com/platform/cli/start) dans la doc Scalingo.
* Vous devez avoir configuré un accès SSH à Scalingo. Vérifiez avec : `$ scalingo keys` -- votre clé SSH doit apparaître dans la liste. Pour configurer l'accès, voir [SSH Key Setup](https://doc.scalingo.com/platform/getting-started/first-steps#ssh-key-setup) dans la doc Scalingo.
* La commande `sshd` doit être disponible. Vérifiez avec : `$ which sshd`. Pour l'installer sous Linux : `sudo apt install openssh-server`.
* Enregistrez votre clé SSH auprès du serveur SSH local : `$ ssh-copy-id $(whoami)@localhost`.

**Utilisation**

Lancez la commande suivante :

```
./tools/scalingodbtunnel APP
```

Remplacez `APP` par l'application Scalingo cible.

Cette commande démarre un tunnel SSH vers la base de données, et le relaie à Docker pour que le conteneur PHP/Doctrine puisse y accéder.

La commande affiche ensuite la `DATABASE_URL`. **Reportez cette valeur** dans `.env.local` pour utiliser la base de données distante. Accédez à http://localhost:8000 comme d'habitude.

Laissez la commande tourner pour garder le tunnel ouvert.

Pour arrêter le tunnel, utilisez <kbd>Ctrl+C</kbd>.
