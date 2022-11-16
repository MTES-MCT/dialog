# 001 - Format d'échange de données

* Date : 2022-11-15
* Personnes impliquées : Florimond Manca, Mathieu Marchois
* Statut : Accepté

**Table des matières**

* [Contexte](#contexte)
* [Décision](#décision)
* [Conséquences](#conséquences)
* [Options envisagées](#options-envisagées)
  * [Option 1 - Réutilisation de DATEX II](#option-1---réutilisation-de-datex-ii)
  * [Option 2 - Adaptation ad-hoc de DATEX II](#option-2---adaptation-ad-hoc-de-datex-ii)
  * [Option 3 - Utilisation de TN-ITS](#option-3---utilisation-de-tn-its)
* [Références](#références)

## Contexte

DiaLog publiera des données d'arrêtés de circulation qui seront utilisées par des services numériques d'aide au déplacement pour prendre en compte par exemple les restrictions poids lourds.

Nous devons donc décider d'un format de données à utiliser.

Faut-il créer un format ad-hoc, ou y a-t-il des standards reconnus sur lesquels s'appuyer ? Dans ce cas, faut-il les réutiliser directement, ou en produire une adaptation ?

**Contraintes**

* Favoriser les standards existants.
* Être compatible avec l'outillage et la connaissance habituelle des contributeurs BetaGouv (exemples : JS, Python, APIs JSON, dépôts git, bases de données relationnelles).
* Pouvoir travailler sur le schéma de données avec un outillage libre. Exemple : ne pas nécessiter une installation Windows ou autre logiciel propriétaire.

**Hypothèses**

* Pas de contrainte sur le format de données de la part des services numériques d'aide au déplacement. Ils sont éventuellement familiers des standards existants.

## Décision

Nous avons décidé d'exposer les données comme des publications `TrafficRegulationPublication` au sens de DATEX II v3.3 (Option 1 décrite ci-dessous).

## Conséquences

* Une documentation à destination des contributeurs sera créée pour prendre en main le standard DATEX II et la façon dont il est utilisé dans DiaLog. Cela incluera des indications pour travailler avec les fichiers XML et XSD.
* Le modèle conceptuel de données (MCD) de DiaLog devra permettre que l'API expose les données au format choisi. Cela sera facilité s'il adopte une terminologie et une structure proches du format choisi.

## Options envisagées

### Option 1 - Réutilisation de DATEX II

Il s'agit d'utiliser directement le schéma de données défini par DATEX II, notamment dans le package [TrafficRegulation](https://docs.datex2.eu/trafficregulation/index.html).

[Comme suggéré par DATEX II](https://docs.datex2.eu/profiling/index.html), nous n'utiliserons qu'une sous-partie de ce standard.

Tous les champs de `TrafficRegulation` ne seront donc pas nécessairement utilisés.

En revanche, toute information que l'on exposera le sera au format défini par ce package, sous peine d'incompatibilité avec le standard.

Le travail sur le modèle conceptuel de données (MCD) (voir [ADR-002]) a suggéré que notre besoin de modélisation de réglementation de circulation était bien couvert par `TrafficRegulation`.

[ADR-002]: https://github.com/MTES-MCT/dialog/blob/main/adr/002_mcd.md

Les notions suivantes sont notamment couvertes :

* Informations générales sur la réglementation, incluant un identifiant, une description, l'autorité émettrice ;
* Définition du type de réglementation ;
* Définition des périodes de validité ou de non-application de la réglementation ;
* Définition de conditions concernant le périmètre géographique ;
* Définition de conditions sur les caractéristiques ou l'affectation du véhicule ;
* Possibilité de combiner ces conditions pour refléter la richesse de la réglementation routière (combinaison de conditions booléennes par opérateurs NOT, AND, OR, XOR) ;

Le référencement des lieux ([_location referencing_](https://docs.datex2.eu/location/index.html)) pourra se faire notamment avec [GML (OpenGIS Geography Markup Language)](https://www.ogc.org/standards/gml). Celui-ci est en effet [supporté par DATEX II](https://docs.datex2.eu/location/7_Gml.html) et permet de faire référence à des points (exemple : route barrée), des lignes (exemple : segment de route) ou des surfaces (exemple : zones à faible émission) par des coordonnées (latitude, longitude) dans un système de coordonnées standard (EPSG 4326).

Si, au cours des développements, nous réalisons que nous avons besoin de modéliser un domaine métier non-couvert par DATEX II, nous pourrons utiliser le [mécanisme d'extension](https://docs.datex2.eu/expert/level3extensionguide.html), probablement au niveau A (extension des énumérations) ou B (modification avec interopérabilité avec le niveau A).

La publication des données pourra prendre la forme d'un endpoint XML dont la conception est hors cadre de cette décision. Cet endpoint pourrait par exemple, grâce à des filtres, permettre de récupérer la réglementation applicable sur une plage temporelle donnée et dans un périmètre géographique donné.

#### Avantages

* Avantages liés à l'utilisation d'un standard : réduit la maintenance (pas de format ad-hoc à créer et à maintenir) et favorise l'interopérabilité entre DiaLog et tout type de système tiers.
* Fournit une modélisation prête à l'emploi.
* Intégration facilitée avec les services numériques d'assistance au déplacement. DATEX II, en tant que standard, est en effet déjà utilisé par certains d'entre eux. Voir par exemple [TomTom - API Incidents DATEXII](https://developer.tomtom.com/intermediate-traffic-service/documentation/service/tomtom-traffic-incidents-intermediate-service-datex-ii) ou [Here - API Traffic Data Service](https://developer.here.com/documentation/traffic-data-service/dev_guide/index.html).

#### Risques

* Barrière à l'entrée non-nulle pour des contributeurs BetaGouv. En effet, bien que le standard soit agnostique, DATEX II émane de l'écosystème Java. Or la communauté BetaGouv est plutôt orientée Python, JavaScript, APIs JSON (sur 454 dépôts à date, [seuls 3 dépôts](https://github.com/betagouv/?q=&type=all&language=java&sort=) de l'organisation `betagouv` utilisent du Java).
  * Il faudra donc un effort de documentation et d'accueil pour travailler avec XML et DATEX II.
* Le mécanisme d'extension n'a pas l'air trivial. Sa [documentation](https://docs.datex2.eu/expert/level3extensionguide.html) fait appel aux outils Java : UML, XSD... Encore une fois peu connu des contributeurs BetaGouv. Or, on risque de devoir au minimum devoir étendre les énumérations (extension de niveau A).
  * Risque peut être réduit en essayant de créer dès à présent une extension de niveau A (enums) voire B (nouvelles propriétés).

### Option 2 - Adaptation ad-hoc de DATEX II

Il s'agit ici de créer un format ad-hoc mais inspiré de DATEX II.

La modélisation proposée par DATEX II semblant couvrir nos besoins, il ne semble pas pertinent de créer notre propre format s'inspirant de DATEX II. Autant le réutiliser directement (Option 1).

### Option 3 - Utilisation de TN-ITS

[TN-ITS](https://tn-its.eu/) propose un format "concurrent" de DATEX II dans le domaine de la réglementation statique. Ce standard [fait partie de NAPCORE](https://napcore.eu/tn-its/).

À date, d'après les [schémas](http://spec.tn-its.eu/schemas/) et [codelists](http://spec.tn-its.eu/codelists/) (entités), TN-ITS se concentre visiblement sur les attributs statiques de la route ("_static road attributes_"). Il est, dans ce domaine, largement compatible avec DATEX II. Voir par exemple la modélisation des [conditions](http://spec.tn-its.eu/schemas/Conditions.xsd) ou du [référencement géographique](http://spec.tn-its.eu/schemas/LocationReferencing.xsd) (_location referencing_).

Avantages

* Avantages liés à l'utilisation d'un standard.
* Nous ne partirions pas de rien.

Risques

* L'adoption de TN-ITS en pratique nous est inconnue à ce stade.
* Contrairement à DATEX II, TN-ITS ne permet pas de modéliser la réglementation temporaire. Par exemple, il n'y a pas de notion de "période de validité".
* La documentation de TN-ITS est plus parcellaire que celle de DATEX II.
* Même barrière à l'entrée que DATEX II pour les contributeurs BetaGouv du fait qu'il s'agit également d'un format XML.

## Références

Liens utiles :

* [MTES-MCT/dialog#1: Etudier DATEX II](https://github.com/MTES-MCT/dialog/issues/1)
* [Documentation DATEX II](https://docs.datex2.eu/)
* [NAPCORE](https://napcore.eu/) (National Access Point Coordination Organisation for Europe) - Une organisation qui coordonne et harmonise les plateformes de mobilité au niveau européen.
* [TN-ITS](https://napcore.eu/tn-its/) - Standard d'échange de données qui fait partie de NAPCORE. Se focalise sur les attributs statiques des routes.

Glossaire

* ITS : _Intelligent Transport Systems_ - Une approche visant à collecter, stocker et fournir des données de trafic routier en temps réel. Voir https://www.itsstandards.eu.
* (R)TTI : _(Realtime) Traffic and Travel Information_
