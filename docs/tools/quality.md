# Qualité

## Tests

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

## Formatage

Pour lancer le formatage automatique (`php-cs-fixer`) :

```bash
make format
```

## Vérifications

Pour lancer les vérifications, utilisez la commande :

```bash
make check
```

Cela lance notamment `php-cs-fixer` en mode _dry run_, l'analyse statique [PHPStan](https://phpstan.org/), le linter Twig, et vérifie le schéma de la base de données.
