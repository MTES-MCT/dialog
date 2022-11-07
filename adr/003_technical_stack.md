# Environnement technique

* Date : 2022-09-10
* Personnes impliquées : Mathieu Marchois, Florimond Manca
* Statut : BROUILLON

## Contexte

Dans le cadre du projet DiaLog, nous devons choisir un environnement technique sur lequel on peut se reposer pour numériser un arrêté de circulation et le diffuser auprès des _calculateurs d'itinéraires_ via une _API_.

## Décision

### Base de données

Nous avons choisi d'utiliser [PostgreSQL](https://www.postgresql.org/) en version 14 pour les raisons suivantes :

* Robuste, stable et open-source
* Performant avec un large volume de données
* L'extension [PostGIS](https://postgis.net/) permet de l'utiliser comme une base de données SIG en ajoutant le support d'objets géographiques.

### Language / Framework

Nous avons choisi d'utiliser [PHP](https://php.net) en version 8 et [Symfony](https://symfony.com) en version 6. Le raisonnement détaillé est évoqué ci-dessous.

## Conséquences

* Une base technique PHP/Symfony/PostgreSQL sera mise en place.
* La documentation indiquera des ressources pour démarrer en PHP/Symfony, préférentiellement à destination d'utilisateurs de Python/Django ou Node.js.

## Détails

Nous (Mathieu et Florimond)  avons démarré le projet en octobre 2022 et nous étions familiers avec les langages suivants :

* Python (Florimond) ;
* JavaScript/Node.js (les deux) ;
* PHP (Mathieu).

Python est un langage très connu dans l'écosystème BetaGouv. Néanmoins, l'écosystème Python manque d'un framework web complet (qui inclue des fonctionnalités essentielles de sécurité, de cache, etc) et suffisamment souple pour s'accommoder d'une architecture de type [Architecture Hexagonale](https://www.elao.com/blog/dev/architecture-hexagonale-symfony), laquelle favorise la maintenabilité. Django est sans doute le plus complet, mais il est très _opinionated_ : il est difficile d'adopter une autre architecture sans se battre contre le framework.

Concernant le JS, en toute sincérité, nous souffrions de [JS Fatigue](https://medium.com/@ericclemmons/javascript-fatigue-48d4011b6fc4). Exemple : il faut intégrer TypeScript et la complexité de la transpilation pour bénéficier d'un typage statique, tandis que ce dernier est désormais possible nativement en Python ou PHP.

Concernant PHP, il souffre d'idées préconçues qui ne semblent plus applicables aujourd'hui. [Sa performance semble largement suffisante](https://benchmarksgame-team.pages.debian.net/benchmarksgame/fastest/php.html) grâce à des améliorations comme le [_JIT (just in time) compiler_](https://php.watch/versions/8.0/JIT) en version 8. Le language lui-même s'est amélioré en corrigeant les incohérences des débuts. Cela explique certainement pourquoi il s'agit encore d'un langage parmi les plus utilisés (voir [sondages JetBrains 2021](https://www.jetbrains.com/lp/devecosystem-2021/)).

Côté framework, Symfony a le même statut dans la communauté PHP que Django dans la communauté Python. C'est l'un des frameworks PHP les plus utilisés. Il dispose d'un écosystème très complet et, point différenciant par rapport à Django, d'une vaste communauté francophone. Il fut en effet créé par [SensioLabs](https://sensiolabs.com/fr/) en 2005 et a été téléchargé plus d'un milliard de fois.

Nous avons donc jugé que pour des personnes déjà à l'aise avec Python et Django, comme cela est courant au sein de BetaGouv, la barrière à l'entrée était suffisamment faible pour reprendre un projet en **PHP/Symfony**.
