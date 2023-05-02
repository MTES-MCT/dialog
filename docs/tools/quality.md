# Qualité

## Tests

Nous utilisons le framework [PHPUnit](https://phpunit.de/) pour faire nos tests, ainsi que [Playwright](https://playwright.dev) pour les tests end-to-end (E2E).

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

Exécuter les tests E2E uniquement :

```bash
make test_e2e
```

Passez des arguments Playwright supplémentaires à l'aide de `ARGS="..."`. Par exemple, pour lancer un unique test :

```bash
make test_e2e ARGS="tests/e2e/list.spec.js"
```

Voir la [documentation Playwright](https://playwright.dev/docs/debug#playwright-inspector) pour toutes les options de débogage disponibles.

Pour visualiser le rapport de tests E2E :

```bash
make report_e2e
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
