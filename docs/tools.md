# Outils

## Qualité

### Tests

Nous utilisons le framework [PHPUnit](https://phpunit.de/) pour faire nos tests.

### Tests unitaires

Executer les tests unitaires :

```bash
make phpunit_unit
```

### Linter

Pour lancer le linter PHP :

```bash
make lint_php
```

Pour lancer le linter Twig :

```bash
make lint_twig
```

Pour lancer les linter PHP & Twig :
```bash
make lint
```

## Base de données

### Connection

Pour se connecter au client PostgreSQL, utilisez la commande :

```bash
make database_connect
```

### Migrations

Lorsque vous effectuez des modifications sur les entités doctrine ainsi que sur les fichiers de mapping, vous devez générer une migration pour qu'elle soit versionnée.

Pour générer une migration, utilisez la commande :

```bash
make database_generate_migration
```

Une fois la migration générée, il faut l'executer. Pour ce faire il existe la commande suivante qui va prendre l'ensemble des migrations non jouées et les executer une à une.

```bash
make database_run_migration
```

## PHP

### Intégration avec l'éditeur de code

Pour travailler confortablement sur le projet, il est recommandé de munir son éditeur de texte ou IDE favori d'extensions PHP. Cela permettra de voir les éventuelles erreurs, ou de refactorer le code.

Si vous n'avez pas d'installation PHP sur votre machine, vous pouvez utiliser le binaire PHP inclus dans le conteneur `app` pour les besoins de votre éditeur de texte.

Pour cela, essayez d'indiquer à votre éditeur de texte le chemin `docker-compose exec app php`. Si cela ne fonctionne pas, vous aurez peut-être besoin de créer un fichier `~/.local/bin/php` (si ce chemin est dans votre `$PATH`) ou `/usr/local/bin/php` sur votre machine qui lancera cette commande. Voir [cette discussion StackOverflow](https://stackoverflow.com/questions/53501925/visualstudio-code-php-executablepath-in-docker).
