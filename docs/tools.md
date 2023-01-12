# Outils

## Qualité

### Tests

Nous utilisons le framework [PHPUnit](https://phpunit.de/) pour faire nos tests.

Exécuter tous les tests :

```bash
make test
```

Exécuter les tests unitaires uniquement :

```bash
make test_unit
```

Exécuter les tests d'intégration uniquement :

```bash
make test_integration
```

### Formatage

Pour lancer le formatage automatique (`php-cs-fixer`) :

```bash
make format
```

### Vérifications

Pour lancer les vérifications, utilisez la commande :

```bash
make check
```

Cela lance notamment `php-cs-fixer` en mode _dry run_, l'analyse statique [PHPStan](https://phpstan.org/), le linter Twig, et vérifie le schéma de la base de données.

## Monitoring

Pour monitorer notre serveur de production, nous utilisons le Sentry de beta.gouv.

Pour pouvoir accéder au Sentry Dialog :

1. Se connecter et / ou se créer un compte sur https://sentry.incubateur.net.
2. Demander à un membre de l'équipe actuelle de vous envoyer une invitation pour rejoindre l'organisation.
3. Se rendre sur https://sentry.incubateur.net/organizations/betagouv/projects/dialog/?project=71

## Base de données

### Connexion

Pour se connecter au client PostgreSQL, utilisez la commande :

```bash
make dbshell
```

### Migrations

Lorsque vous effectuez des modifications sur les entités doctrine ainsi que sur les fichiers de mapping, vous devez générer une migration pour qu'elle soit versionnée.

Pour générer une migration, utilisez la commande :

```bash
make dbmigration
```

Une fois la migration générée, il faut l'executer. Pour ce faire il existe la commande suivante qui va prendre l'ensemble des migrations non jouées et les executer une à une.

```bash
make dbmigrate
```

### pgAdmin4

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

## PHP

### Intégration avec l'éditeur de code

Pour travailler confortablement sur le projet, il est recommandé de munir son éditeur de texte ou IDE favori d'extensions PHP. Cela permettra de voir les éventuelles erreurs, ou de refactorer le code.

Si vous n'avez pas d'installation PHP sur votre machine, vous pouvez utiliser le binaire PHP inclus dans le conteneur `app` pour les besoins de votre éditeur de texte.

Pour cela, essayez d'indiquer à votre éditeur de texte le chemin `docker-compose exec app php`. Si cela ne fonctionne pas, vous aurez peut-être besoin de créer un fichier `~/.local/bin/php` (si ce chemin est dans votre `$PATH`) ou `/usr/local/bin/php` sur votre machine qui lancera cette commande. Voir [cette discussion StackOverflow](https://stackoverflow.com/questions/53501925/visualstudio-code-php-executablepath-in-docker).

## Frontend

### DSFR

Cette application web utilise le [Système de Design de l'État (DSFR)](https://www.systeme-de-design.gouv.fr).

Un [thème de formulaires personnalisé](https://symfony.com/doc/current/form/form_themes.html#reusing-parts-of-a-built-in-form-theme) est défini dans `templates/form/dsfr_theme.html.twig`. Il facilite l'utilisation du DSFR pour le [rendu des formulaires](https://symfony.com/doc/current/form/form_customization.html).

Ce thème permet d'utiliser les options suivantes :

| Variable | Utilisation |
|---|---|
| `group_class` | Classe CSS du groupe d'un champs de formulaire. |
| `group_error_class` | Classe CSS du groupe d'un champs de formulaire en état d'erreur. Valeur par défaut : `group_class ~ '--error'`. Par exemple, si `group_class` vaut `'fr-input-group'`, alors `group_error_class` sera `'fr-input-group--error'`. |
| `widget_class` | Classe CSS du widget d'un champs de formulaire. |
| `widget_error_class` | Classe CSS du widget d'un champs de formulaire en état d'erreur. Valeur par défaut : `widget_class ~ '--error'`. Par exemple, si `widget_class` vaut `'fr-select'`, alors `widget_error_class` sera `'fr-select--error'`. |

Exemple pour le rendu d'un [champ de saisie (input)](https://www.systeme-de-design.gouv.fr/elements-d-interface/composants/champ-de-saisie) nommé `description` :

```twig
{{ form_row(form.description, {group_class: 'fr-input-group', widget_class: 'fr-input'}) }}
```
