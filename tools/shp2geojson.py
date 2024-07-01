#!/usr/bin/env python3
import argparse
import subprocess
import sys
from pathlib import Path


def _run_dockerized_ogr2ogr(shapefile: Path):
    assert shapefile.is_absolute()

    outputfile = shapefile.parent / (shapefile.stem + '.geojson')

    command = [
        "docker",
        "compose",
        "-f",
        "docker-compose.yml",
        "--profile",
        "gdal",
        "run",
        "--rm",
        "-v",
        f"{shapefile.parent}:/home/data/",
        "gdal",
        "ogr2ogr",
        # https://gdal.org/drivers/vector/shapefile.html
        "-of",
        "GeoJSON",
        "-t_srs",
        "EPSG:4326",
        f"/home/data/{outputfile.name}",
        f"/home/data/{shapefile.name}",
    ]

    return subprocess.run(command)


def main(shapefile: Path) -> int:
    result = _run_dockerized_ogr2ogr(shapefile)

    if result.returncode:
        print(
            "ERROR: ogr2ogr command failed, see output above",
            file=sys.stderr,
        )
        return 1

    return 0


def _path_check_exists(value: str) -> Path:
    path = Path(value)

    if not path.exists():
        raise argparse.ArgumentTypeError(f"file or directory not found: {value}")

    return path


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "shapefile",
        type=_path_check_exists,
        help="Path to shapefile (.shp)",
    )
    args = parser.parse_args()

    sys.exit(
        main(
            shapefile=args.shapefile.absolute(),  # ogr2ogr requires absolute paths
        )
    )
