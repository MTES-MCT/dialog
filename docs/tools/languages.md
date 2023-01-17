# Langages

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
