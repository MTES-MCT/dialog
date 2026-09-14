# DiaLog
[![CI](https://github.com/MTES-MCT/dialog/actions/workflows/ci.yml/badge.svg)](https://github.com/MTES-MCT/dialog/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/MTES-MCT/dialog/branch/main/graph/badge.svg?token=VJpXBny7YB)](https://codecov.io/gh/MTES-MCT/dialog)

Digitaliser, diagnostiquer, diffuser l’information réglementaire de logistique.

## Environnement technique

- [Docker](https://www.docker.com/) / [Compose](https://docs.docker.com/compose/)
- [PHP](https://www.php.net/)
- [Symfony](https://www.symfony.com/)
- [Twig](https://twig.symfony.com/)
- [Turbo](https://turbo.hotwired.dev/) / [Stimulus](https://stimulus.hotwired.dev/)
- [PostgreSQL](https://www.postgresql.org/)

## Démarrage du projet

ℹ️ Vous devez avoir **[Docker](https://www.docker.com/)** et **[Docker Compose](https://docs.docker.com/compose/)** d'installés sur votre machine.

Tout d'abord, demandez à un membre de l'équipe la valeur de la variable d'environnement `BDTOPO_DATABASE_URL`, et ajoutez-la à `.env.local` (créez le fichier si besoin) :

```bash
# .env.local
BDTOPO_DATABASE_URL=postgres://dialog_app:...
```

Pour démarrer l'application (http://localhost:8000) :

```bash
make install
```

ou lancez la commande suivante si le projet avait déjà été installé :

```bash
make start
```

## Environnements

### 🚀 Production

[https://dialog.beta.gouv.fr](https://dialog.beta.gouv.fr)

### 🛠️ Staging

[https://dialog.incubateur.net](https://dialog.incubateur.net)

### 🛠️ Staging Data

Une branche permettant d'avoir un staging data stable. 

## Contribuer

Consultez [la documentation de développement](./docs/README.md).
