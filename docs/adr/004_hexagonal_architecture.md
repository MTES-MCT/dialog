# 004 - Architecture hexagonale

* Date : 2022-11-16
* Personnes impliquées : Mathieu Marchois, Florimond Manca
* Statut : Brouillon

## Contexte

Dans le cadre du projet DiaLog, nous voulons mettre en place une architecture qui soit évolutive et maintenable dans le temps, tout en étant au plus près du métier.

## Décision

Dans ce contexte, l'architecture de code générale est inspirée des différentes approches suivantes :

* [Domain-Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) (DDD)
* [Architecture hexagonale][0]

Ce choix architectural a été motivé par :

1. Le gain de maintenabilité long-terme observé sur les autres projets développés par [Fairness](https://fairness.coop) (dont les contributeurs principaux initiaux de ce projet sont membres), dû notamment à un haut degré de découplage entre les couches métier, applicatives et techniques.
2. Un objectif de standardisation du projet (fichiers, concepts, processus), facilitant la navigation par toutes et tous, même si le projet grossit en taille et en complexité.

L'implémentation se veut légère et pragmatique. L'objectif premier est bien d'améliorer la maintenabilité, tout en évitant toute "cérémonie" excessive (_boilerplate_ réduit au maximum). Les contributeurs et contributrices ayant plutôt l'habitude de développer à même un framework (Symfony...) remarqueront que ce style architectural, de par le découplage qu'il permet, nécessite un peu plus de code (nombre de fichiers, lignes de code), et une gymnastique intellectuelle un peu différente. Mais la récompense visée est la suivante : un code métier plus pérenne et facile à tester de façon isolée, une application plus facile à décliner sous d'autres formes (ex : API secondaire, CLI, ...), une infrastructure plus facile à faire évoluer en fonction des besoins.

Quelques ressources d'introduction :

- (_Recommandé_) [L'architecture hexagonale avec Symfony][0] - Introduction en français à l'architecture hexagonale, et un exemple d'intégration dans un contexte web (Symfony).
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) - Description originale (relativement théorique) de l'architecture hexagonale par Alistair Cockburn.
- [Domain-Driven Design in dynamic languages](https://github.com/valignatev/ddd-dynamic) - Dépôt GitHub de ressources au sujet du DDD appliqué aux langages typés dynamiquement (Ruby, PHP, etc).

[0]: https://www.elao.com/blog/dev/architecture-hexagonale-symfony

## Description

Le dossier `src/` contient notamment les sous-dossiers suivants :

* `Domain` - Code métier : entités, règles métier, erreurs, et autres interfaces... Cette partie doit utiliser une terminologie métier, compréhensible par toutes et tous (_ubiquitous language_).
* `Application` - Code applicatif : typiquement des commandes (_commands_), requêtes (_queries_), et leurs _handlers_.
* `Infrastructure` - Implémentations concrètes faisant le lien entre l'application et l'infrastructure technique.

Les dossiers `Domain`, `Application` et `Infrastructure` sont divisés en dossiers thématiques.

## Conséquences

- Les couches Domain et Application doivent être agnostiques du framework
- La communication entre les couches doit être déscendante (Infrastructure => Application => Domain) et non l'inverse
- Une documentation (le présent ADR) fournira les bases sur l'approche DDD et l'architecture hexagonale pour faciliter la prise en main par de nouveaux contributeurs ou contributrices.
