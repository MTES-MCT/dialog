# CIFS

## Visualiser la polyligne d'un incident

Copier-coller la polyligne et utiliser cette commande pour la convertir en GeoJSON et l'ouvrir sur https://geojson.io :

```bash
$ ./tools/polyline_to_geojson.py -o "$POLYLIGNE"
```

Exemple :

```console
$ ./tools/polyline_to_geojson.py -o "45 2 45.5 2.5"
{"type": "LineString", "coordinates": [[2.0, 45.0], [2.5, 45.5]]}
--> Opening: https://geojson.io/#data=data:application/json,%7B%22type%22%3A%20%22LineString%22%2C%20%22coordinates%22%3A%20%5B%5B2.0%2C%2045.0%5D%2C%20%5B2.5%2C%2045.5%5D%5D%7D
```
