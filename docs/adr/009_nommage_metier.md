# 009 - Convention de nommage des termes métier

* Créatrion : 2024-05-02
* Personnes impliquées : Florimond Manca, Mathieu Marchois, Julien Jacquelinet, équipe DiaLog
* Statut : Accepté

## Contexte

L'architecture de code de DiaLog s'appuie notamment sur les principes du <abbr title="Domain-Driven Design">DDD</abbr> (voir [ADR-004 - Architecture hexagonale](./004_hexagonal_architecture.md)).

Un des principes du DDD est le [_ubiquitous language_](https://thedomaindrivendesign.io/developing-the-ubiquitous-language/), langage commun à toutes les parties prenantes du logiciel (développement, produit, client, etc).

Jusqu'ici les termes métier tels que "mesure" ou "arrêté" étaient traduits en anglais ("_measure_", "_regulation order_", etc). Cela a donné lieu à des débats, notamment lors du ticket [#738 Refacto de la table Location](https://github.com/MTES-MCT/dialog/issues/738).

Cette approche n'ayant pas été explicitement décidée par l'ADR-004, la question s'est posée de continuer comme cela ou bien de passer à un nommage des termes métier en français dans le code.

## Décision

Les termes métier continueront à être traduits en anglais, dans la limite du compréhensible (option 1 ci-dessous).

Cette décision sera documentée, en l'occurence par le présent ADR.

Résumé de la motivation : éviter un travail de refonte jugé trop important par rapport au bénéfice pour l'équipe et/ou, si cette refonte est réalisée progressivement, une charge mentale supplémentaire liée à la cohabitation de termes métier en anglais et en français.

## Options envisagées

### Option 1 : Continuer le nommage en anglais et le documenter

Cette option consiste à continuer de nommer les termes métier en anglais dans le code, mais à documenter ce choix par le présent ADR.

Avantages

* Pas de travail supplémentaire ; pas besoin de retravailler le code existant

Inconvénients

* La traduction en anglais continue de poser des problèmes
  * Sa pratique suppose que l'équipe de développement soit à l'aise avec l'anglais, y compris dans ses subtilités pour ne pas choisir de traductions "faux-ami", maladroites ou sources de confusion, ce qui n'est pas forcément le cas.
  * L'usage de termes métier en anglais peut donc engendrer des incompréhensions, ambigüités ou malentendus, à rebours de l'intérêt d'un _ubiquitous language_.
  * Même en cas de maîtrise de l'anglais, un terme métier en français n'a pas forcément d'équivalent fidèle ou évident dans le monde anglo-saxon. Voir [ce fil Twitter](https://nitter.poast.org/kindrobot_org/status/1740062151229022367) avec le cas de termes métier relatifs à la Sécurité Sociale.
    * Néanmoins le contexte métier de DiaLog (la réglementation de circulation) est dans une certaine mesure moins spécifique au cadre français. De plus, en pratique un équivalent satisfaisant (aidé par DATEX II) a toujours pu être trouvé jusqu'ici.
* Le coût du changement continuera à l'avenir de grandir à mesure que le code de DiaLog grandit, car l'existence de ces problèmes implique qu'il s'agît d'une forme de dette technique. (Cette dynamique motive d'autant plus le besoin de prendre une décision et de s'y tenir.)

### Option 2 : transition immédiate vers un nommage en français

Cette option consiste à passer à un nommage des termes métier en français, en repassant sur tout le code existant en une fois pour éviter toute période de transition pouvant être source de confusion.

Avantages

* Les problèmes liées aux difficultés de traduction (cf option 1) sont résolus
* Le principe du language ubiquitaire est mieux respecté

Inconvénients

* Ampleur du travail très importante : renommer presque toutes les entités du `Domain`, ainsi que les noms de tables en base de données, et les composants annexes comme les vues, queries  commands et leurs handlers, controllers, templates HTML, tests, etc.
* Cette refonte introduit un risque d'erreur et donc d'introduction de dysfonctionnements. En effet, la PR résultante ferait plusieurs dizaines de milliers de lignes de diff (DiaLog a environ 32 000 lignes de code actuellement, cf `$ cloc src templates tests`).
* Le gel de la base de code le temps de cette refonte ne serait pas pratique, et pourtant la réalisation de cette refonte pendant que le code continue à être modifié avec les termes anglais peut lui faire prendre du retard.

### Option 3 : transition progressive vers un nommage en français

Cette option consiste à introduire le nommage en français pour tout nouveau développement, et à convertir le code existant petit à petit.

Plusieurs approches peuvent être employées

Avantages

* Les problèmes liées aux difficultés de traduction (cf option 1) sont résolus
* Le principe du language ubiquitaire est mieux respecté
* Il n'est pas nécessaire de geler la base de code pendant le renommage

Inconvénients

* L'ampleur du travail n'en est pas moindre que l'option 2, elle est juste répartie dans le temps.
* Sauf à lui donner la priorité sur d'autres développements, le risque que le renommage prenne du temps et donc laisse la base de code dans un état "intermédiaire" est réel.
* La cohabitation entre termes anglais et français qui en résultera peut elle-même être source de confusion et de malentendus, potentiellement plus grande que l'emploi de termes en anglais.
