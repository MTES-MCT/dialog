# 003 - Environnement technique

* Création : 2022-09-14
* Dernière mise à jour : 2023-07-17
* Personnes impliquées : Mathieu Marchois, Florimond Manca
* Statut : Accepté

## Contexte

Dans le cadre du projet DiaLog, nous devons choisir un environnement technique sur lequel on peut se reposer pour numériser un arrêté de circulation et le diffuser auprès des calculateurs d'itinéraires via une API.

**Contraintes**

* Doit être compatible avec une démarche d'éco-conception de service numérique (questionner le besoin, réduire l'impact sur les terminaux, etc).
* Le langage doit disposer d'un système de typage statique.
* Le langage doit disposer d'un framework web _full-stack_ (qui permet de travailler avec la base de données comme des vues HTML).

## Décision

### Base de données

Nous avons choisi d'utiliser [PostgreSQL](https://www.postgresql.org/) en version 14 pour les raisons suivantes :

* Robuste, stable et open-source
* Performant avec un large volume de données
* L'extension [PostGIS](https://postgis.net/) permet de l'utiliser comme une base de données SIG en ajoutant le support d'objets géographiques.

### Language / Framework

Nous avons choisi d'utiliser [PHP](https://php.net) en version 8 et [Symfony](https://symfony.com) en version 6 (à la date de création de ce document). Le raisonnement détaillé est évoqué ci-dessous.

Concernant le frontend, nous avons choisi une **approche hybride** de type "_HTML with sprinkles of JS_" (HTML saupoudré de JS).

En effet, le degré d'interactivité envisagé pour DiaLog étant limité (pages web, liens et formulaires), il n'a pas été jugé pertinent d'adopter dès le début un framework JS SPA (React, SvelteKit, etc).

L'essentiel du frontend est donc géré avec Symfony lui-même, via des templates [Twig](https://twig.symfony.com/) (équivalent de Jinja2 en Python).

Les éléments d'interactivté sont gérés via [Turbo](https://turbo.hotwired.dev) (mise à jour sélective de l'interface via des fragments HTML échangés entre le navigateur et le serveur), et [Stimulus](https://stimulus.hotwired.dev) (interactivité côté client).

Cette approche devrait contribuer à l'écoconception du service numérique, ainsi qu'à son accessibilité pour les utilisateurs munis d'un équipement informatique contraint (matériel vieillissant, réseau ADSL...).

## Conséquences

* Une base technique PHP/Symfony/PostgreSQL est mise en place.
* La documentation indique des ressources pour démarrer en PHP/Symfony, préférentiellement à destination d'utilisateurs de Python/Django ou Node.js.

## Détails

### Choix du langage PHP

Nous (Mathieu et Florimond)  avons démarré le projet en octobre 2022 et nous étions familiers avec les langages suivants :

* Python (Florimond) ;
* JavaScript/Node.js (les deux) ;
* PHP (Mathieu).

Python est un langage très connu dans l'écosystème BetaGouv. Néanmoins, l'écosystème Python manque d'un framework web qui soit complet (qui inclue des fonctionnalités essentielles de sécurité, de cache, etc) et suffisamment souple pour s'accommoder d'une architecture de type [Architecture Hexagonale](https://www.elao.com/blog/dev/architecture-hexagonale-symfony) qui favorise la maintenabilité. Django est sans doute le plus complet, mais il est très _opinionated_ : il est difficile d'adopter une autre architecture sans se battre contre le framework.

Concernant le JS, en toute sincérité, nous souffrions de [JS Fatigue](https://medium.com/@ericclemmons/javascript-fatigue-48d4011b6fc4). Exemple : il faut intégrer TypeScript et la complexité de la transpilation pour bénéficier d'un typage statique, tandis que ce dernier est désormais possible nativement en Python ou PHP.

Concernant PHP, à notre sens il souffre d'idées préconçues qui ne semblent plus applicables aujourd'hui. [Sa performance semble largement suffisante](https://benchmarksgame-team.pages.debian.net/benchmarksgame/fastest/php.html) grâce à des améliorations comme le [_JIT (just in time) compiler_](https://php.watch/versions/8.0/JIT) en version 8. Le language lui-même s'est amélioré en corrigeant les incohérences des débuts. Cela explique certainement pourquoi il s'agit encore d'un langage parmi les plus utilisés (voir [sondages JetBrains 2021](https://www.jetbrains.com/lp/devecosystem-2021/)).

Côté framework, Symfony a le même statut dans la communauté PHP que Django dans la communauté Python. C'est l'un des frameworks PHP les plus utilisés. Il dispose d'un écosystème très complet et, point différenciant par rapport à Django, d'une vaste communauté francophone. Il fut en effet créé par [SensioLabs](https://sensiolabs.com/fr/) en 2005 et a été téléchargé plus d'un milliard de fois.

Nous avons donc jugé que pour des personnes déjà à l'aise avec Python et Django, comme cela est courant au sein de BetaGouv, la barrière à l'entrée était suffisamment faible pour reprendre un projet en **PHP/Symfony**.

### Choix de Turbo et Stimulus

Le choix de ne pas recourir à un framework JS SPA provient aussi d'une volonté de réduire la complexité de la stack frontend. Les évolutions récentes des frameworks SPA (_server-side component rendering_, _rehydration_ et autres) ne nous ont pas convaincu de leur pertinence pour notre cas d'usage de site métier avec peu d'interactivité et géré par une petite équipe. Il nous a semblé plus adapté de repartir d'une base HTML.

À la différence d'une architecture "Multi-page app" (MPA) historique, où chaque navigation déclenchait un rechargement complet de la page, des éléments d'interactivité visant à améliorer l'expérience utilisateur (UX) sont possibles grâce à Turbo et Stimulus.

* Turbo fournit des outils pour mettre à jour certaines zones de la page à partir de fragments HTML renvoyés par le serveur. En pratique, il intercepte les navigations natives du navigateur (liens, formulaires) et interprète la réponse du serveur pour mettre à jour la page de façon sélective. **N.B.** : Il n'y a donc pas d'API JSON entre le serveur et le navigateur : le serveur et le navigateur échangent du HTML tout-fait.
* Stimulus est une librairie JavaScript qui permet d'ajouter des comportements interactifs à des éléments de la page (exemples : afficher une modale, autocompléter un champ de recherche, ...).

Note : d'autres outils similaires existent. Par exemple [htmx](https://htmx.org) est similaire à Turbo ; [Catalyst](https://catalyst.rocks/) est similaire à Stimulus. Turbo et Stimulus ont été choisis pour leur intégration native à Symfony via le projet "_Symfony UX_".

Cette approche constitue un entre-deux entre les MPA (tout-serveur) et les SPA (tout-client). Elle est déjà utilisée à l'échelle. Par exemple, c'est l'approche adoptée par GitHub (_cf_ les éléments `<turbo-frame>` dans le code source HTML de ses pages).

Nous nous appuyons sur les standards du Web (HTTP, HTML, CSS, JS) et leurs développements récents. Par exemple, les modales sont gérées via l'élément natif `<dialog>`. L'appui sur les standards devrait favoriser la durabilité et la portabilité du service.

Cette approche réduit aussi en principe (évaluations à faire) la charge sur les terminaux utilisateurs, en réduisant considérablement la quantité de JavaScript nécessaire. Cela contribue donc à l'écoconception du service numérique, ainsi qu'à l'accessibilité du service à des utilisateurs dont la qualité de connexion et d'équipement est variable (exemple : petites communes rurales avec ordinateur ancienne génération et réseau ADSL).
