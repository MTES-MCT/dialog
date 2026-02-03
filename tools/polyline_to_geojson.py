#!/usr/bin/env python3
"""
Convertit une polyline CIFS (format "lat lon lat lon ...") en GeoJSON LineString ou MultiLineString
pour affichage et vérification sur une carte (ex. geojson.io).

Usage:
  # Coller la polyline sur stdin (pratique pour une longue chaîne)
  echo "47.482746462 -1.749676073 47.482739294 -1.749542406 ..." | python3 tools/polyline_to_geojson.py -o

  # Ou passer la polyline en argument
  python3 tools/polyline_to_geojson.py "47.48 -1.74 47.49 -1.75" -o

  # Sortie MultiLineString (une seule ligne)
  python3 tools/polyline_to_geojson.py --multi -o < polyline.txt

  # -o / --open-browser : ouvre le résultat dans geojson.io
"""
import argparse
import json
import sys
import webbrowser
from urllib.parse import quote


def polyline_to_coords(polyline: str) -> list[list[float]]:
    """Convertit 'lat lon lat lon ...' en liste [[lon, lat], ...] pour GeoJSON."""
    parts = polyline.strip().split()
    if len(parts) % 2 != 0:
        raise ValueError("La polyline doit contenir un nombre pair de nombres (lat lon lat lon ...)")
    coords = [float(c) for c in parts]
    # CIFS = lat lon ; GeoJSON = [lon, lat]
    lats = coords[::2]
    lons = coords[1::2]
    return [list(p) for p in zip(lons, lats)]


def main(polylines: list[str], multi: bool = False, open_browser: bool = False) -> None:
    for polyline in polylines:
        polyline = polyline.strip()
        if not polyline:
            continue
        coords = polyline_to_coords(polyline)
        if len(coords) < 2:
            print("Au moins 2 points requis (4 nombres).", file=sys.stderr)
            continue
        if multi:
            geometry = {"type": "MultiLineString", "coordinates": [coords]}
        else:
            geometry = {"type": "LineString", "coordinates": coords}
        geojson = json.dumps(geometry)
        print(geojson)
        if open_browser:
            url = "https://geojson.io/#data=data:application/json," + quote(geojson)
            print(f"---> Ouverture: {url}", file=sys.stderr)
            webbrowser.open(url)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Convertit une polyline CIFS (lat lon lat lon ...) en GeoJSON pour affichage sur une carte."
    )
    parser.add_argument(
        "polyline",
        nargs="*",
        help="Polyline en arguments. Si vide, lecture sur stdin (une ligne = une polyline).",
    )
    parser.add_argument(
        "-m",
        "--multi",
        action="store_true",
        help="Sortie en MultiLineString (une seule ligne dans le multi).",
    )
    parser.add_argument(
        "-o",
        "--open-browser",
        action="store_true",
        help="Ouvre le résultat dans geojson.io pour visualiser sur la carte.",
    )
    args = parser.parse_args()

    if args.polyline:
        polylines = [" ".join(args.polyline)]
    else:
        polylines = [line for line in sys.stdin]
    if not polylines:
        print("Usage: coller la polyline sur stdin, ou la passer en argument.", file=sys.stderr)
        print("Ex: echo '47.48 -1.74 47.49 -1.75' | python3 tools/polyline_to_geojson.py -o", file=sys.stderr)
        sys.exit(1)
    main(polylines=polylines, multi=args.multi, open_browser=args.open_browser)
