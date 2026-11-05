# Géométries des emprises

## Orientation des sections

Une emprise délimitée par deux points (une voie nommée « du n° 47 au n° 65 », ou une route numérotée « du PR 12 au PR 15 ») est enregistrée avec une géométrie découpée dans la BD TOPO par `LineSectionMaker`.

Depuis octobre 2026, cette géométrie est **toujours orientée du point A (début) vers le point B (fin)** : son premier sommet est le plus proche de A, son dernier le plus proche de B. Les morceaux connectés sont fusionnés en une seule ligne ; les morceaux disjoints sont ordonnés par distance croissante à A.

Le sens de circulation concerné par la mesure n'est **pas** porté par la géométrie mais par la colonne `direction` des tables `named_street` et `numbered_road` (`BOTH`, `A_TO_B`, `B_TO_A`). Ce sont les exports qui l'interprètent :

- **API JSON** : le champ `direction` est exposé tel quel sur `namedStreet` et `numberedRoad`, relativement aux points A et B décrits dans la même réponse.
- **CIFS (Waze)** : un incident `ONE_DIRECTION` porte son sens dans l'ordre des points de la polyligne. Pour une restriction `B_TO_A`, la polyligne est donc retournée à l'export.

Les voies entières n'ont pas de point A ni B : leur sens est forcé à `BOTH` par le formulaire et leur géométrie n'est pas orientée.

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
