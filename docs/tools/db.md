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
