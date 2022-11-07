# DiaLog

Digitaliser, diagnostiquer, diffuser l’information réglementaire de logistique.

## Environnement technique

- [Docker](https://www.docker.com/)
- [PHP](https://www.php.net/)
- [Symfony](https://www.symfony.com/)
- [Twig](https://twig.symfony.com/)
- [Htmx](https://htmx.org/)
- [PostgreSQL](https://www.postgresql.org/)

## Prérequis

Vous devez avoir **[Docker](https://www.docker.com/)** et **[Docker Compose](https://docs.docker.com/compose/)** d'installés sur votre machine pour pouvoir lancer les services `database` et `app` définis dans le fichier `docker-compose.yml`.

## Démarrage du projet

### Installation
Pour installer le projet la première fois, lancer la commande :

```bash
make install
```

Cette commande va installer les différentes dépendances, dont la base de données, et lancer l'app.

### Démarage

Pour démarrer les services, lancer la commande :

```bash
make start
```

L'application sera disponible sur `http://localhost:8000`.

## Qualité

Nous utilisons [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) pour formater le code PHP en respectant le standard défini par `Symfony`.

Pour lancer le linter :

```bash
make lint
```
