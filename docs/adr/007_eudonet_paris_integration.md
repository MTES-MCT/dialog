# 007 - Intégration avec Eudonet Paris

* Date : 2023-08-29
* Personnes impliquées : Florimond Manca (auteur principal), Mathieu Marchois et Léa Lefoulon (relecture technique), équipe DiaLog (relecture et commentaires)
* Statut : ACCEPTÉ <!-- [BROUILLON|ACCEPTÉ|REJETÉ|DÉPRÉCIÉ] -->

## Contexte

Dans le cadre du cas d'usage "Métropole", l'équipe travaille sur une intégration avec le système de gestion d'arrêtés de la Ville de Paris, qu'on appelle ici **Eudonet Paris** (voir [#343](https://github.com/MTES-MCT/dialog/issues/343)).

[Eudonet](https://fr.eudonet.com/) est un logiciel CRM générique édité par la société du même nom. La Ville de Paris a une configuration particulière de ce CRM pour ses besoins.

À ce jour, un enjeu important pour l'équipe est d'**exposer un volume conséquent d'arrêtés** via l'export DATEX II afin de faciliter l'avancement de l'intégration avec les services GPS.

Il est ressorti d'une précédente exploration qu'une portion non-négligeable des localisations des arrêtés d'Eudonet Paris utilise des **intersections** pour délimiter les sections de voie, c'est-à-dire des rues de début et de fin. Or l'API Adresse, le géocodeur utilisé jusqu'ici, ne contient que des POI de rues ou d'adresses. Une solution a dû être trouvée.

Cet ADR documente la solution initiale qui a été mise en place pour ingérer et exposer des arrêtés issus du système Eudonet Paris.

### Contraintes

#### Volume de données

La base de données de production d'Eudonet Paris contient environ 300 000 arrêtés (permanents et temporaires).

Le connecteur serait chargé d'intégrer les données remplissant l'ensemble de ces conditions :

* Les arrêtés temporaires
* Qui sont ou seront bientôt en vigueur (= ceux dont la date de fin est dans le futur)
* Restrictions de circulation seulement

Ne sont donc pas dans le périmètre, notamment :

* Les arrêtés permanents
* Les interdictions de stationnement
* Les arrêtés temporaires passés

Compte tenu de ce périmètre, le volume du stock d'arrêtés à intégrer en production serait de l'ordre de quelques centaines à quelques milliers maximum.

## Décision

Nous avons choisi -- au moins dans un premier temps -- l'option 1, décrite ci-dessous.

## Conséquences

* De l'outillage sera ajouté au dépôt `dialog` pour construire le bundle de données Addok ou le mettre à jour, et lancer l'instance Addok locale.
* Le bundle sera construit une première fois, puis hébergé sur le kDrive partagé pour être facilement réutilisé localement.
* Un outil d'import en ligne de commande sera créé.
* De la documentation sur ces différents outils sera ajoutée pour faciliter leur prise en main.
* Ces changements feront l'objet d'une PR qui sera revue par l'équipe technique.

## Options envisagées

### Option 1 - Import avec commande manuelle et Addok local

Dans cette option, l'import d'arrêtés se fait manuellement à l'aide d'un **outil en ligne de commande**.

```bash
make console CMD="app:eudonet_paris:import"
```

Cette commande effectue des requêtes à l'[API JSON d'Eudonet Paris](https://eudonet-partage.apps.paris.fr/eudoapi/eudoapidoc/swaggerui/).

Les données de l'API sont traitées pour ajouter les arrêtés à la base DiaLog en mode "Publié" (lecture seule), en réutilisant le code métier existant.

L'**organisation** dans laquelle les arrêtés sont créés est configurée par la variable d'environnement `APP_EUDONET_PARIS_ORG_UUID`. Elle est obligatoire pour lancer l'outil. En pratique, il faudra pointer vers l'organisation de la Ville de Paris.

Une fois les arrêtés importés dans la base de données DiaLog, ils sont visibles dans l'**UI** et inclus dans l'**export DATEX II**.

Les **localisations** sont géocodées à l'aide d'une instance Addok locale. ([Addok](https://github.com/addok/addok) est le logiciel libre de recherche de POI qui propulse l'[API Adresse](https://adresse.data.gouv.fr/api-doc/adresse).) Les données de l'instance locale proviendront d'un **bundle personnalisé** contenant les données de la **BAN** ainsi que des POI d'intersection calculés à partir des données **OpenStreetMap France**.

Un système de **déduplication** est prévu grâce à un champ `source` ajouté à `RegulationOrderRecord`. Il vaut `dialog` par défaut. La commande d'import utilisera la source `eudonet_paris`. En filtrant les arrêtés dont l'identifiant est parmi ceux des arrêtés provenant de `eudonet_paris`, on peut évite de traiter une nouvelle fois les arrêtés déjà importés.

L'outil produira un **log** contenant un résumé de l'exécution : nombre d'arrêtés Eudonet Paris traités, ajoutés, ignorés ; liste des identifiants des arrêtés ajoutés ; liste des identifiants et justifications pour les arrêtés ignorés. Ce log pourra éventuellement être enregistré dans le dépôt à des fins d'historique.

**Avantages**

* Pas d'infrastructure supplémentaire à gérer : il n'y a rien à déployer à part héberger le bundle Addok.
* Adapté à la volumétrie faible envisagée : l'exécution de la commande d'import ne devrait pas dépasser quelques minutes.
* Les éventuelles erreurs sont immédiatement visibles dans le terminal.
* Si les champs DiaLog ou Eudonet Paris changent, la situation peut être corrigée en modifiant notre code : pas d'intervention nécessaire côté Eudonet Paris.

**Risques**

* Une fois un arrêté intégré, les mises à jour côté Eudonet Paris ne seront pas prises en compte, en raison du système de déduplication.
  * Un arrêté peut toujours être supprimé manuellement, soit par l'équipe technique en se connectant à la base de données, soit par l'administrateur en rejoignant l'organisation Ville de Paris (les arrêtés seront alors visibles dans l'UI, dont le bouton "Supprimer").
* Le setup Addok local risque d'être complexe à maîtriser, ce qui ne facilite pas la maintenabilité et la reprise par un membre de l'équipe technique.
  * Pour réduire ce risque : lors de la review, au moins 1 membre de l'équipe technique devra avoir pu démarrer l'Addok local et importer les données Eudonet Paris dans sa base de données locale.
* Addok risque de ne pas suffire lorsqu'on implémentera la gestion des intersections dans l'UI de DiaLog, en particulier en fonction de l'UX qui sera prévue.
  * Addok n'a pas été développé pour gérer des intersections. Le format "Voie A / Voie B CP Ville" est une forme de _hack_.
  * Ce format ne permet de représenter que des intersections au sein de la même ville. Est-ce un problème ?

### Option 2 - Import avec commande manuelle et intersections BD TOPO ou BD CARTO

Dans cette variante de l'option 1, la source des POI d'intersections ne serait pas OpenStreetMap France, mais la [BD TOPO](https://geoservices.ign.fr/bdtopo) ou la [BD CARTO](https://geoservices.ign.fr/bdcarto).

La BD TOPO comme la BD CARTO contiennent les linéaires routiers français et sont en principe cohérents avec la BAN.

L'utilisation de l'une ou l'autre de ces bases aurait été similaire : télécharger la base et l'importer dans un PostgreSQL afin d'y soumettre des requêtes PostGIS de calcul d'intersections au moment de l'import.

Calcul d'une intersection : les routes sont représentées par des formes vectorielles dont "l'épaisseur" varie entre la BD TOPO et la BD CARTO en raison de la différence d'échelle (1m pour la BD TOPO et 10m pour la BD CARTO). L'intersection entre deux routes est donc un polygone et non pas un point. En prenant le centroïde de ce polygone, on obtient le point d'intersection.

**Avantages**

* On pourrait obtenir l'intersection entre n'importe quel couple de voies, y compris si elles sont dans des villes différentes (cas d'usage EPCI).
* Cohérent avec la BAN, contrairement à OpenStreetMap pour lequel des incohérences pourraient exister.
* Gestion encore plus simple qu'Addok : pas besoin de gérer un bundle personnalisé, le calcul d'intersection est instantanné grâce à une requête PostGIS.

**Risques**

* Ces deux bases sont plus volumineuses, ce qui accroît les temps de téléchargement.
  * La base devrait être déployée sur un PostgreSQL distant pour éviter à chaque membre de l'équipe technique de la configurer localement. Cela s'accompagnerait d'un coût d'hébergement.
* Le travail a déjà été fait avec OpenStreetMap. Il faudrait redévelopper l'outillage et la requête PostGIS. (Mais gare au biais des coûts irrécupérables.)

### Option 3 - Import avec commande manuelle et API Adresse

L'API Adresse ne contient que les données de la BAN.

Dans cette option, on ne pourrait donc pas importer les mesures s'appliquant sur des localisations définies par des intersections. Or il y en a un nombre non-négligeable dans le système Eudonet Paris.

### Option 4 - Exécution automatisée et Addok distant

Dans cette option, l'outil en ligne de commande est lancé périodiquement via un cronjob, déployé par exemple avec [Scalingo Scheduler](https://doc.scalingo.com/platform/app/task-scheduling/scalingo-scheduler).

**Avantages**

* Intégration périodique des nouveaux arrêtés d'Eudonet Paris, sans intervention manuelle

**Risques**

* Infrastructure supplémentaire nécessaire (cronjob, instance Addok), avec toutes les problématiques "ops" associées (gestion de code, gestion de configuration, monitoring, ...).
* Gestion des erreurs : comment savoir si le cronjob a échoué, et que faire dans ce cas ? Comment éviter que des arrêtés ne soient "oubliés" en cas d'erreurs ?

### Option 5 - Adapter la base Eudonet Paris avec des champs spécifiques à DiaLog

Plutôt que de traduire les champs Eudonet Paris au format DiaLog, on pourrait demander à l'équipe Eudonet Paris de créer des champs spécifiques à DiaLog, respectant un schéma de données à établir.

Par exemple, un champ "Adresse DiaLog" pourrait être ajouté. Il correspondrait au champ "Adresse" de DiaLog (exemple de valeur : "Rue de la Concertation, 75018 Paris").

**Avantages**

* Potentiellement moins de travail pour l'équipe DiaLog
* Les éventuelles incompatibilités ou manques entre les données Eudonet Paris et le format DiaLog (et par suite DATEX II) émergeraient dès leur saisie dans Eudonet Paris, plutôt qu'après avoir constaté des échecs à les ingérer via l'outil d'import.

**Risques**

* Cela irait à l'encontre d'une minimisation d'interférence avec le processus de travail habituel de l'organisation :
  * Charge de travail supplémentaire considérable pour l'équipe Eudonet Paris. L'équipe devrait repasser sur tous les arrêtés (des centaines de milliers !) pour y remplir les champs DiaLog, en plus de modifier la configuration Eudonet pour créer ces champs.
  * Si notre schéma de données change, la charge d'adaptation du connecteur pèserait aussi sur l'équipe Eudonet Paris.

## Références

* [#343 Connecteur Eudonet (Ville de Paris)](https://github.com/MTES-MCT/dialog/issues/343)
* [Pad d'exploration Eudonet Paris](https://pad.incubateur.net/9Z_s_o6bQ76l0SWad6Ryzw#)
* [Eudonet](https://fr.eudonet.com/)
* [Documentation Swagger de l'API Eudonet Paris](https://eudonet-partage.apps.paris.fr/eudoapi/eudoapidoc/swaggerui/)
* [Lexique de l'API Eudonet Paris](https://eudonet-partage.apps.paris.fr/eudoapi/eudoapidoc/lexique_FR.html)

## Annexes

#### Aperçu de l'API Eudonet

Les données issues d'Eudonet sont structurées en "tables" et en "champs".

Les tables et les champs sont identifiés par un DESC_ID.

Par exemple :

* La table "Arrêtés" a un DESC_ID = 1100
* Le champ "Date de début" de la table "Arrêtés" a un DESC_ID = 1109

Ces champs définis par la configuration Eudonet de la Ville de Paris. L'ensemble des tables et des champs présents dans Eudonet Paris peut être récupérés via l'endpoint [MetaInfos](https://eudonet-partage.apps.paris.fr/eudoapi/eudoapidoc/swaggerui/#!/MetaInfos/post_MetaInfos) de l'API Eudonet. C'est d'ailleurs ainsi que les DESC_ID mentionnés dans ce document ont été identifiés.

#### Localisations Eudonet Paris

Les localisations dans Eudonet Paris sont de plusieurs types, identifié par le champ "Porte sur".

Chaque type a des champs afférents qui peuvent être remplis de plusieurs façons :

* "La totalité de la voie"
  * Libellé voie
* "Une section" OU "Un axe"
  * Libellé voie
  * _Début ou fin défini par un POI de type numéro de maison :_
    * N° adresse début
    * N° adresse fin
    * Libellé adresse début
    * Libellé adresse fin
  * _Début ou fin défini par un POI de type intersection (si les champs ci-dessus sont vides) :_
    * Libellé voie début
    * Libellé voie fin

Types de localisations que l'on propose d'ignorer dans le cadre de cette décision :

* "Une zone"
* "Un point" (exemple : une place publique fermée à la circulation piétonne)

#### Format et qualité des données Eudonet Paris

Les données d'Eudonet Paris n'obéissent pas à un format standard.

Certes, la forme des données renvoyées par l'API Eudonet est (probablement) commune à toutes les instances du CRM Eudonet.

Mais le contenu de ces données (tables et champs) est propre à l'instance Eudonet Paris.

Il est donc nécessaire de traiter les éventuels cas particuliers et inconsistances acceptables des données issues d'Eudonet Paris.

Pour les données inexploitables en l'état, un travail serait à prévoir avec l'équipe Eudonet Paris pour les améliorer à la source.

Exemples de libellés de voie inexploitables par un géocodeur :

* Avenue de Saint-Mandé, 12ème arrondissement, au droit du N°22, au droit des n° 22-24 7
* Avenue des Terroirs de France, 12ème arrondissement, au droit du N°10, 33
