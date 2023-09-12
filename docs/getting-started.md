# Bien démarrer

Vous venez d'arriver sur le projet ? Voici quelques indications pour vous mettre en route.

Table des matières

* [Lancer DiaLog en local](#lancer-dialog-en-local)
* [Contribuer au projet](#contribuer-au-projet)
* [Prise en main des technologies utilisées](#prise-en-main-des-technologies-utilisées)

## Lancer DiaLog en local

Pour lancer DiaLog en local, suivez les instructions du [README](../README.md#démarrage-du-projet). Hormis l'installation de Docker, il n'y a normalement pas d'autre prérequis.

Vous arrivez à accéder à un DiaLog local sur http://localhost:8000 ? Bravo !

## Contribuer au projet

Nous utilisons un processus de contribution classique basé sur git et des pull requests (PR).

Pour contribuer du code ou de la documentation :

1. Créez une nouvelle branche dans votre dépôt git local
2. Faites vos modifications
3. Ouvrez une PR.

La PR devra être relue et approuvée avant d'être mergée dans `main`.

Le déploiement se fait automatiquement après un merge dans `main`. Un déploiement est aussi créé pour chaque PR. Pour en savoir plus, voir la [documentation de déploiement](./deployment/README.md).

## Prise en main des technologies utilisées

Pour contribuer du code, vous allez probablement avoir besoin de vous familiariser avec les technologies utilisées dans ce dépôt. Voici quelques indications à cet effet.

**En bref** : DiaLog utilise le langage PHP avec le framework Symfony. Il n'y a pas de frontend JS séparé. À la place, DiaLog utilise des templates Twig (Twig est à PHP ce que Jinja2 est à Python) combinés aux outils de la suite Hotwire (Turbo, Stimulus) pour l'interactivité UI. Pour en savoir plus, consultez l'[ADR-003 - Environnement technique](./adr/003_technical_stack.md).

### PHP et Symfony

Pour démarrer avec Symfony, nous recommandons de parcourir à son rythme la [documentation Symfony](https://symfony.com/doc/current/index.html).

Si vous avez déjà développé avec un framework "full-stack / MVC" comme Django, vous devriez vite trouver vos repères. N'hésitez pas à consulter des comparatifs, par exemple [Django vs Symfony, slant.co](https://www.slant.co/versus/1746/3758/~django_vs_symfony).

Passez un peu de temps sur la partie [Configuration](https://symfony.com/doc/current/configuration.html) pour comprendre le dossier `config/` et son contenu. De même, essayez d'appréhender la partie [Services / DI](https://symfony.com/doc/current/service_container.html) car le système d'injection de dépendances (DI) est important dans Symfony.

### Turbo et Stimulus

Pour démarrer avec Turbo et Stimulus, nous recommandons de :

* Consulter la [page d'accueil de Hotwire](https://hotwired.dev/). Elle présente la philosophie générale de l'approche "HTML Over the Wire".
* Parcourir à son rythme la [documentation Turbo](https://turbo.hotwired.dev/), en particulier le [handbook Turbo](https://turbo.hotwired.dev/handbook/introduction). Intéressez-vous en particulier à ces notions : Drive, Frames, Streams.
* Parcourir à son rythme la [documentation Stimulus](https://stimulus.hotwired.dev/), en particulier le [handbook Stimulus](https://stimulus.hotwired.dev/handbook/introduction). Intéressez-vous en particulier à ces notions : Controllers, Values, Targets, Actions, Outlets.
* Consulter la [documentation de Symfony UX Turbo](https://symfony.com/bundles/ux-turbo/current/index.html) pour vous familiariser avec l'intégration de Turbo / Stimulus dans Symfony.

Pour appréhender les aspects plus philosophiques des approches qui sous-tendent ces outils, vous pouvez aussi consulter :

* Le e-book [Modest JS Works](https://modestjs.works/) : apporte une critique raisonnée du tout-SPA et promeut l'idée d'un "Gradient du JS" pour la réalisation des parties interactives d'une UI Web.
* Le e-book [Hypermedia Systems](https://hypermedia.systems/) : invite à revisiter les fondements du Web pour simplifier l'architecture des applications grâce à une approche "hypermedia" capable d'assouvir la plupart des besoins. Les principes généraux qui y sont présentés sont illustrés avec [htmx](https://htmx.org), une technologie [similaire à Turbo mais plus bas niveau](https://www.reddit.com/r/django/comments/ppuguf/how_does_htmx_compare_to_turbo_hotwired/hd7rs3h/?utm_source=share&utm_medium=web3x&utm_name=web3xcss&utm_term=1&utm_content=share_button).

### DATEX II

DATEX II est le standard choisi comme format d'échange pour les données mises à disposition des GPS en open data. Pour en savoir plus, voir l'[ADR-002 - Format d'échanges de données](./adr/001_exchangeformat.md).

Pour appréhender DATEX II et ses définitions XML, vous pouvez consulter le [tutoriel "Comment utiliser DATEX II"](./tutorials/datex2.md).

### Architecture du code

Le code de DiaLog suit une structure particulière, inspirée du Domain-Driven Design, de la Clean Architecture et de l'Architecture Hexagonale. Pour en savoir plus et en appréhender les principes généraux, consultez l'[ADR-004 - Architecture hexagonale](./adr/004_hexagonal_architecture.md).

**Conséquences pour l'utilisation de Symfony**

Notre utilisation de Symfony s'accompagne de bonnes pratiques de découplage, telles que la configuration de l'ORM Doctrine ou de la validation dans des fichiers XML plutôt que directement dans le code PHP sous forme d'annotations.

Le code peut donc différer de ce qui est généralement présenté par défaut dans la documentation Symfony.
