#!/usr/bin/env python3
import argparse
import json
import webbrowser
from urllib.parse import quote


def main(polylines: list[str], open_browser: bool = False):
    for polyline in polylines:
        # "40 2 41 2.5 ..." -> [40, 2, 41, 2.5]
        coords = [float(c) for c in polyline.split(" ")]

        ys = coords[::2]  # -> [40, 41]
        xs = coords[1::2]  # -> [2, 2.5]

        points = list(zip(xs, ys))  # -> [[2, 40], [2.5, 41]]

        geometry = {"type": "LineString", "coordinates": points}
        geojson = json.dumps(geometry)
        print(geojson)

        if open_browser:
            # https://geojson.io/#url-api
            url = "https://geojson.io/#data=data:application/json," + quote(geojson)
            print(f"---> Opening: {url}")
            webbrowser.open(url)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Convert CIFS polylines to GeoJSON")
    parser.add_argument("polyline", nargs="+")
    parser.add_argument(
        "-o", "--open-browser", action="store_true", help="Show result in geojson.io"
    )
    args = parser.parse_args()

    main(polylines=args.polyline, open_browser=args.open_browser)
