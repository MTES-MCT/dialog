# Géométries des emprises

## Orientation des sections

Une emprise délimitée par deux points (une voie nommée « du n° 47 au n° 65 », ou une route numérotée « du PR 12 au PR 15 ») est enregistrée avec une géométrie découpée dans la BD TOPO par `LineSectionMaker`.

Depuis octobre 2026, cette géométrie est **toujours orientée du point A (début) vers le point B (fin)** : son premier sommet est le plus proche de A, son dernier le plus proche de B. Les morceaux connectés sont fusionnés en une seule ligne ; les morceaux disjoints sont ordonnés par distance croissante à A.

Le sens de circulation concerné par la mesure n'est **pas** porté par la géométrie mais par la colonne `direction` des tables `named_street` et `numbered_road` (`BOTH`, `A_TO_B`, `B_TO_A`). Ce sont les exports qui l'interprètent :

- **API JSON** : le champ `direction` est exposé tel quel sur `namedStreet` et `numberedRoad`, relativement aux points A et B décrits dans la même réponse.
- **CIFS (Waze)** : un incident `ONE_DIRECTION` porte son sens dans l'ordre des points de la polyligne. Pour une restriction `B_TO_A`, la polyligne est donc retournée à l'export. Voies nommées et routes numérotées sont concernées.
- **Export CSV** : les colonnes `emprise_debut`, `emprise_fin` et `sens_circulation` décrivent les points A et B et le sens relatif à ces points.
- **DATEX II** : l'extension DiaLog `dx:linearLocationExtended` porte, à côté de `geoJsonGeometry`, un élément `directionRelativeOnLinearSection` valant `both`, `aligned` (de A vers B, dans l'ordre des sommets) ou `opposite` (de B vers A). Le XSD est dans `docs/spec/datex2/DATEXII_3_DiaLogExtension.xsd`.

Un **tracé libre** porte lui aussi un sens (`raw_geojson.direction`) : A est le premier point dessiné, B le dernier. Quand des exceptions sont soustraites du tracé, le résultat est réorienté dans l'ordre du dessin par `LineOrienter`, le même service qui oriente les sections.

Une **voie entière** n'a pas de point A ni B saisis : `LineOrienter::normalize()` les fixe de façon déterministe (A est l'extrémité la plus à l'ouest, puis la plus au sud ; B l'extrémité la plus éloignée de A) et oriente la géométrie fusionnée de A vers B. L'aperçu du formulaire et l'enregistrement passent par le même calcul, donc les marqueurs A et B affichés correspondent au sens enregistré. Depuis novembre 2026, le sens est saisissable pour une voie entière.

## Aperçu dans le formulaire

Le serveur renvoie à l'aperçu (`fragment_location_geometry`) une géométrie orientée de A vers B. Le module `assets/maps/direction.js` en déduit les marqueurs A et B (extrémités) et, pour `A_TO_B` ou `B_TO_A`, une flèche au milieu du tracé ; les marqueurs ne sont affichés que lorsque A et B ont un sens (voie entière cochée, section avec ses deux points, route numérotée avec ses deux PR). Sur le tracé libre, la carte de dessin marque A au premier point et B au dernier dès que le select « Sens concerné » est présent.

## Commandes

### `app:location:geometry:sync`

Calcule la géométrie des emprises qui n'en ont pas encore (par exemple après un échec de géocodage à la saisie).

```bash
make console CMD="app:location:geometry:sync"
```

### `app:location:geometry:orient`

Recalcule la géométrie des emprises à sens unique (`direction` différent de `BOTH`) délimitées par une section, pour qu'elle respecte l'orientation A vers B. C'est la migration des emprises enregistrées avant l'orientation des sections.

Le calcul est le même que lors d'une modification de l'emprise depuis le formulaire : géocodage de A et B (API Adresse pour un numéro de voie, BD TOPO pour une intersection ou un point de repère), découpage puis orientation. Une emprise dont le géocodage échoue est laissée telle quelle et listée en erreur.

```bash
# Recalcule et compare sans rien enregistrer
make console CMD="app:location:geometry:orient --dry-run"

# Enregistre les géométries recalculées
make console CMD="app:location:geometry:orient"
```

La commande écrit une ligne JSON de synthèse (`num_locations`, `num_updated`, `num_unchanged`), une ligne `DEBUG` par emprise modifiée (`would update` en mode `--dry-run`) et une ligne `ERROR` par échec de géocodage. Le code de retour est non nul dès qu'un géocodage a échoué.

Elle est relançable sans risque : une emprise déjà orientée est comptée dans `num_unchanged`.

En production, l'exécuter une fois après le déploiement de l'orientation des sections, d'abord en `--dry-run` puis sans l'option, dans un conteneur one-off Scalingo (`scalingo --app <app> run php bin/console app:location:geometry:orient --dry-run`).
