# Outils

## Qualité

### Tests

Nous utilisons le framework [PHPUnit](https://phpunit.de/) pour faire nos tests.

Executer les tests unitaires :

```bash
make phpunit_unit
```

Executer les tests d'intégrations :

```bash
make phpunit_integration
```

### Linter

Pour lancer le linter PHP :

```bash
make php_lint
```

Pour lancer le linter Twig :

```bash
make twig_lint
```

Pour lancer les linter PHP & Twig :
```bash
make lint
```

### Analyses statiques de code

Pour lancer l'analyse statique de code [PHPStan](https://phpstan.org/), utilisez la commande :

```bash
make phpstan
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

### Outillage

Pour valider que le schéma de la base de données est correct, vous pouvez lancer la commande suivante :

```bash
make console CMD="doctrine:schema:validate"
```

## PHP

### Intégration avec l'éditeur de code

Pour travailler confortablement sur le projet, il est recommandé de munir son éditeur de texte ou IDE favori d'extensions PHP. Cela permettra de voir les éventuelles erreurs, ou de refactorer le code.

Si vous n'avez pas d'installation PHP sur votre machine, vous pouvez utiliser le binaire PHP inclus dans le conteneur `app` pour les besoins de votre éditeur de texte.

Pour cela, essayez d'indiquer à votre éditeur de texte le chemin `docker-compose exec app php`. Si cela ne fonctionne pas, vous aurez peut-être besoin de créer un fichier `~/.local/bin/php` (si ce chemin est dans votre `$PATH`) ou `/usr/local/bin/php` sur votre machine qui lancera cette commande. Voir [cette discussion StackOverflow](https://stackoverflow.com/questions/53501925/visualstudio-code-php-executablepath-in-docker).
