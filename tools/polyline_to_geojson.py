#!/usr/bin/env python3
import argparse
import json


def main(polylines: list[str]):
    for polyline in polylines:
        # "40 2 41 2.5 ..." -> [40, 2, 41, 2.5]
        coords = [float(c) for c in polyline.split(" ")]

        ys = coords[::2]  # -> [40, 41]
        xs = coords[1::2]  # -> [2, 2.5]

        points = list(zip(xs, ys))  # -> [[2, 40], [2.5, 41]]

        print(
            json.dumps(
                {
                    "type": "LineString",
                    "coordinates": points,
                }
            )
        )


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Convert CIFS polylines to GeoJSON")
    parser.add_argument("polyline", nargs="+")
    args = parser.parse_args()

    main(polylines=args.polyline)
