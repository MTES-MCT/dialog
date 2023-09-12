# Comment utiliser DATEX II

Le standard [DATEX II](https://docs.datex2.eu/) peut être un peu intimidant à première vue. Cette documentation a pour objectif de vous l'approprier.

## Généralités

DATEX II est un standard de format d'échange de données au format XML pour le trafic routier.

La version 3.2 du standard a ajouté un schéma de réglementation routière : [TrafficRegulation](https://docs.datex2.eu/trafficregulation/index.html).

DiaLog utilise ce schéma comme format d'échange de données avec les services numériques d'aide au déplacement. Pour en savoir plus, voir [ADR-001 - Format d'échange de données](../adr/001_exchangeformat.md).

## Démarrer avec le XML

On l'a dit, DATEX II est un format XML.

Si, comme nous, vous aviez plutôt l'habitude d'échanger des données JSON, cela pourrait vous intimider : allez-vous devoir vous plonger dans le monde de SOAP, J2E, et autre bus MOM ? Restons calmes. Certes, DATEX II baigne dans cet environnement-là : la documentation [Developers' corner](https://docs.datex2.eu/developers/) ne mentionne que JAXB, l'outil Java pour lier son code à du XML. Mais pour nos besoins, vous n'aurez pas besoin de vous reconvertir au Java.

Il va en revanche vous falloir quelques **bases sur le XML**.

### Les bases de XML

Commencez par ici : [Learn XML in Y Minutes](https://learnxinyminutes.com/docs/xml).

Cette petite page vous montrera les bases de la syntaxe XML.

Vous devriez en sortir à l'aise sur ces notions :

* Elément
* Attribut
* Noeuds enfants, _nesting_
* Document bien formé (_wellformed document_).

C'est bon ? Alors voici un exemple de document XML. Que décrit-il ?

```xml
<?xml version="1.0" encoding="UTF-8"?>
<bookstore>
  <book category="COOKING">
    <title lang="en">Everyday Italian</title>
    <author>Giada De Laurentiis</author>
    <year>2005</year>
    <price>30.00</price>
  </book>
  <book category="CHILDREN">
    <title lang="en">Harry Potter</title>
    <author>J K. Rowling</author>
    <year>2005</year>
    <price>29.99</price>
  </book>
  <book category="WEB">
    <title lang="en">Learning XML</title>
    <author>Erik T. Ray</author>
    <year>2003</year>
    <price>39.95</price>
  </book>
</bookstore>
```

On pourrait dire qu'il décrit une bibliothèque contenant 3 livres. Chaque livre a une catégorie, ainsi qu'un titre, un auteur, une année de publication, et un prix.

Vu de loin, XML ressemble à HTML. Mais [XML est différent de HTML](https://www.geeksforgeeks.org/html-vs-xml/). Contrairement à HTML, XML ne décrit pas comment afficher des données ; il décrit les données elles-mêmes.

XML fournit en fait un cadre complet pour le transport de données sur le Web.

### Utiliser XML dans son éditeur de texte

Passons à la pratique :

* Démarrez votre éditeur de texte favori. Assurez-vous qu'il dispose d'outils ou d'extensions adéquates pour travailler avec XML.
* Collez le XML ci-dessus dans un fichier `bookstore.xml`. 

Voici alors quelques petits exercices :

* Essayez d'ajouter un 4ème livre de votre choix.
* Comment pourrait-on ajouter une information sur la maison d'édition des livres ?

Pour aller plus loin, parcourez le [tutoriel XML](https://www.w3schools.com/xml/default.asp) du W3C.

### Schémas XSD

Un schéma XSD (pour Schema Definition Language) permettent de définir le schéma auquel doit se conformer un message XML. En quelque sorte, XSD est à XML ce que JSONSchema est à JSON.

Là aussi, le meilleur apprentissage est probablement la pratique.

Alors suivez le [tutoriel XSD](https://www.w3schools.com/xml/schema_schema.asp) du W3C.

Vous devriez en sortir à l'aise sur les notions suivantes :

* Définir un namespace dans le fichier XSD : `xmlns` et `targetNamespace` ;
* Utiliser un schéma XSD dans un fichier XML : `xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"`, `xmlns="{namespace}"` et `xsi:schemaLocation="{namespace} {fichier.xsd}"` ;
* Utiliser un schéma XSD par alias : `xmlns:monalias="{namespace}"` puis référencer les éléments par `<monalias:un-element-exemple>` ;
* Définir un élément avec `<xs:element>` ;
* Définir un objet avec `<xs:element>`, `<xs:complexType>` et `<xs:sequence>` ;
* Définir une liste avec `<xs:element>`, `<xs:complexType>`, `<xs:sequence>` et `<xs:element minOccurs="..." maxOccurs="...">` ;
* Définir un attribut avec `<xs:attribute>` ;
  Où faut-il le placer dans un `<xs:complexType>` ?
  <details>
  <summary>Réponse</summary>
  Directement en enfant du xs:complexType, et xs:sequence.
  </details>
* Définir un enum avec `<xs:simpleType>`, `<xs:restriction`> et `<xs:enumeration>` ;

Faites alors l'exercice : 

* Quel schéma XSD pourrait-on proposer pour le `bookstore.xml` ?
* Comment faut-il modifier `bookstore.xml` pour que l'élément racine `<bookstore>` soit validé avec ce schéma ?
* Modifier `bookstore.xml` pour qu'il utilise les éléments de `bookstore.xsd` avec un alias `b`, par exemple `<b:bookstore>`.

<details>
<summary>Cliquer pour voir une solution</summary>

`books.xsd` :

```xml
<?xml version="1.0" encoding="utf-8" standalone="no"?>
<xs:schema
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns="https://example.org"
  targetNamespace="https://example.org"
  elementFormDefault="qualified"
>
  <xs:simpleType name="CategoryEnum">
    <xs:restriction base="xs:string">
      <xs:enumeration value="WEB"></xs:enumeration>
      <xs:enumeration value="COOKING"></xs:enumeration>
      <xs:enumeration value="CHILDREN"></xs:enumeration>
      <xs:enumeration value="GEOGRAPHY"></xs:enumeration>
    </xs:restriction>
  </xs:simpleType>

  <xs:complexType name="Book">
    <xs:sequence>
      <xs:element name="title">
        <xs:complexType>
          <xs:simpleContent>
            <xs:extension base="xs:string">
              <xs:attribute name="lang" type="xs:string"></xs:attribute>
            </xs:extension>
          </xs:simpleContent>
        </xs:complexType>
      </xs:element>
      <xs:element name="author" type="xs:string"></xs:element>
      <xs:element name="year" type="xs:gYear"></xs:element>
      <xs:element name="price" type="xs:float"></xs:element>
    </xs:sequence>
    <xs:attribute name="category" type="xs:string"></xs:attribute>
  </xs:complexType>

  <xs:complexType name="Bookstore">
    <xs:sequence>
      <xs:element name="book" type="Book" minOccurs="0" maxOccurs="unbounded"></xs:element>
    </xs:sequence>
  </xs:complexType>

  <xs:element name="bookstore" type="Bookstore"></xs:element>
</xs:schema>
```

`bookstore.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<bookstore
  xmlns="https://example.org"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="https://example.org bookstore.xsd"
>
    <!-- ... -->
</bookstore>
```
</details>

## DATEX II

### DATEX II et XML

Une fois les bases du XML assimilées, vous devriez mieux pouvoir naviguer dans le standard DATEX II.

En effet, tout compte fait, DATEX II fournit essentiellement un ensemble de schémas XSD. Ces schémas permettent de valider qu'un contenu XML est conforme au standard.

Nous avons ajouté les schémas XSD que DiaLog utilise dans [spec/datex2](https://github.com/MTES-MCT/dialog/tree/master/spec/datex2).

Voici à nouveau quelques exercices :

* Inspectez `example.xml`. De quel type de publication DATEX II s'agit-il ?
* Modifiez le fichier pour que les éléments issus de http://datex2.eu/schema/3/common soient utilisés non plus avec `com:{élément}` mais `c:{élément}`.
* À quoi ressemblerait un fichier XML qui décrit une `SituationPublication` ?

### Obtenir les schémas XSD

Malheureusement, la page [téléchargements](https://docs.datex2.eu/downloads/modelv33.html) de DATEX II ne fournit pas des schémas XSD valides. Arrivez-vous à savoir pourquoi ? Indice : les schémas utilisent des alias non-définis...

Par ailleurs, cette page ne fournit que les fichiers XSD individuels, mais certains schémas dépendent eux-mêmes d'autres schémas.

La solution est de recourir au [Webtool](https://webtool.datex2.eu/wizard/) fourni par DATEX II pour générer la sous-partie de DATEX II qui nous intéresse.

Si d'aventure vous deviez récupérer à nouveau ces schémas, voici comment procéder.

* Ouvrir le [Webtool](https://webtool.datex2.eu/wizard/).
* Dans "1. Source", choisir V3.3 DATEX II.
* Cliquer sur "Next" jusqu'à arriver à l'écran "5. Sélection".
* Dans "5. Selection", vérifier que seul TrafficRegulationPublication est coché, puis cliquer sur "Next".
* Dans "6. Options", s'assurer que XML est coché, puis cliquer sur "Next" pour télécharger le `.zip` avec les schémas XSD.

### Explorateur UML

La page [DATEX II Model](https://docs.datex2.eu/_static/data/v3.4/umlmodel/html/index.htm) permet de naviguer dans le format de données DATEX II représenté avec le langage de modélisation UML.

## Avec DATEX II, comment représenter...

#### Une circulation interdite

Utiliser une réglementation `AccessRestriction` de type `noEntry`.

```xml
<trafficRegulation>
  <typeOfRegulation xsi:type="AccessRestriction">
    <accessRestrictionType>noEntry</accessRestrictionType>
  </typeOfRegulation>
  <!-- ... -->
</trafficRegulation>
```

#### Une circulation interdite dans un sens uniquement

[À clarifier]

Deux possibilités pour définir un sens en particulier :

* SingleRoadLinearLocation (extends LinearLocation) > linearWithinLinearElement: LinearElementWithinLinear > directionOnLinearSection: DirectionEnum (aligned, opposite, bothWays, allDirections, ...) 
* PointLocation (extends NetworkLocation) > pointAlongLinearElement: PointAlongLienarElement > directionAtPoint: DirectionEnum
* OpenLrPointLocationReference > openlrOrientation: enum (withLineDirection, againstLineDirection, both, noOrientationOrUnknown)

L'enum `DirectionEnum` ne permet de définir qu'un sens par rapport à un autre : "sens normal" ou "sens du _linearElement_"

* Pour `directionOnLinearSection` et `directionAtPoint` :
  * Par exemple, "_aligned_" signifie "_same direction as the normal direction of flow on the road network_".
  * Mais quel est alors la "_normal direction of flow_" ?
  * :point_right: DATEX II semble supposer une connaissance externe du réseau routier. Les GPS en disposent-ils ?
* Pour les variantes `relative*` : "Direction of traffic flow relative to the direction in which the linear element is defined"

#### Une circulation à sens unique

Utiliser une réglementation `DirectionRestriction` de type `aheadOnly`

```xml
<trafficRegulation>
  <typeOfRegulation xsi:type="DirectionRestriction">
    <directionRestrictionType>aheadOnly</directionRestrictionType>
  </typeOfRegulation>
  <!-- ... -->
</trafficRegulation>
```

#### Une circulation modifiée

Le type de réglementation `AlternateRoadOrCarriagewayOrLaneLayout` définit "une réglementation où une route / voie a temporairement une autre disposition". Utile en cas de travaux par exemple.

Schéma :

```yaml
trafficRegulation:
  typeOfRegulation: # xsi:type="AlternateRoadOrCarriagewayOrLaneLayout"
    ## This class describes a regulation where a road/carriageway/lane has temporarily another layout.
    deviationToHardshoulder: bool # Traffic lanes move across to the right [...]
    deviationToOtherCarrriageway: bool  # The road turns over the oncoming lane
    mergedToOtherLane: bool # Closure of a traffic lane, two lanes to one.
    roadOrCarriagewayOrLaneLayoutType: enum (road, lane, carriageway)
    newLayout: LinearLocation
```

#### Des informations complémentaires sur la ou les voies concernées

Dans DATEX II, les informations liées au réseau routier peuvent être spécifiées dans `PointLocation` ou `LinearLocation`

```yaml
# DATEII_3_LocationReferencing.xsd
PointLocation | LinearLocation (extends NetworkLocation):
  supplementaryPositionalDescription: # SupplementaryPositionalDescription
    carriageway: # Carriageway
      carriageway*: enum
      originalNumberOfLanes: int?
      lane: # Lane[]
        - laneNumber: int?
          laneUsage: enum? (allLanesCompleteCarriageway, leftLane, middleLane, rightLane, emergencyLane, ...)
    roadInformation: # RoadInformation[]
      - roadDestination: str? (ex: "Lille")
        roadName: str? (ex: "Autoroute du Nord")
        roadNumber: str? (ex: "A1")
```

#### Une rue où s'applique une réglementation

À partir d'un nom de rue, numéro de début, numéro de fin

* Là où il faut définir une `LocationReference`, utiliser la variante `SingleRoadLinearLocation` avec `.linearWithinLinearElement` (extends SingleRoadLinearElement, extends LinearElement, extends NetworkLocation, extends Location)
  * Indiquer le nom de la rue dans `.linearElement.roadName`
  * Indiquer (le cas échéant) la direction concernée par rapport au sens défini par `.fromPoint -> .toPoint` dans `.directionRelativeOnLinearSection`
  * Indiquer un début dans `.fromPoint`
    * Utiliser le type `DistanceFromLinearElementReferent`
    * Géocoder le numéro de début (obtenir ses coordonnées GPS lat-lon)
    * Définir `.fromReferent` 
      * Placer les coodonnées GPS dans `.pointCoordinates`
    * Définir `.distanceAlong: 0` (0 mètres à partir du point ainsi défini, c'est-à-dire le point lui-même)
  * Indiquer une fin dans `.toPoint`, même format que `fromPoint`
* Ajouter d'éventuelles informations "humaines" (nom de rue, identifiant de rue (D123, N122, etc), numéros de début et de fin) dans `.supplementaryPositionalDescription.roadInformation[].roadName/Number`

**Exemple**

```xml
<implementedLocation xsi:type="loc:SingleRoadLinearLocation">
  <loc:linearWithinLinearElement>
    <loc:linearElement></loc:linearElement>
    <loc:fromPoint xsi:type="loc:DistanceFromLinearElementReferent">
      <loc:distanceAlong>0</loc:distanceAlong>
      <loc:fromReferent>
        <!-- DATEX II exige un identifiant unique sur ce segment, on utilise 'lat,lon' -->
        <loc:referentIdentifier>47.366334,-1.944703</loc:referentIdentifier>
        <loc:referentType>referenceMarker</loc:referentType>
        <loc:pointCoordinates>
          <loc:latitude>47.366334</loc:latitude>
          <loc:longitude>-1.944703</loc:longitude>
        </loc:pointCoordinates>
      </loc:fromReferent>
    </loc:fromPoint>
    <loc:toPoint xsi:type="loc:DistanceFromLinearElementReferent">
      <loc:distanceAlong>0</loc:distanceAlong>
      <loc:fromReferent>
        <loc:referentIdentifier>47.370631,-1.94021</loc:referentIdentifier>
        <loc:referentType>referenceMarker</loc:referentType>
        <loc:pointCoordinates>
          <loc:latitude>47.370631</loc:latitude>
          <loc:longitude>-1.94021</loc:longitude>
        </loc:pointCoordinates>
      </loc:fromReferent>
    </loc:toPoint>
  </loc:linearWithinLinearElement>
</implementedLocation>
```

#### Une description humaine des lieux

Par exemple, "Au coin de la pharmacie", ou "À partir du  parking à vélo"

C'est possible avec le champ `locationDescription (String)` de `SupplementaryPositionalDescription`.

A priori trop humain pour être utilisé par un calculateur d'itinéraire... Mais peut tout de même être affiché ?

## Références

Ces différentes ressources vous permettront d'en savoir plus sur DATEX II et les composants utilisés dans le cadre de DiaLog.

### XML

Tutoriels

* [XML Tutorial](https://www.w3schools.com/xml/default.asp), w3chools.com. (Notamment les sections "XML Tutorial", "XSD Schema" et "XSD Data Type" dans la barre latérale.)

### DATEX II

Introductions

* [La Norme Européenne DATEX II](http://trafic-routier.data.cerema.fr/la-norme-europeenne-datex-ii-a58.html), Cerema, publié le 30/01/2019.

Documentation officielle

* [Documentation de DATEX II](https://docs.datex2.eu/)
