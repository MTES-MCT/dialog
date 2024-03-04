#!/usr/bin/env python3
# Use this script to wait for a given URL to become available
import argparse
import subprocess
import time
import sys

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("url")
    parser.add_argument("--interval", type=int, default=3)
    parser.add_argument("--max-attempts", type=int, default=5)
    args = parser.parse_args()

    url = args.url
    interval = args.interval
    max_attempts = args.max_attempts

    start_time = time.time()

    print(f"wait-for-url.py: waiting {interval * max_attempts} seconds for {url}")

    for _ in range(max_attempts):
        result = subprocess.run(
            ["curl", "--output", "/dev/null", "--silent", "--fail", url]
        )

        if result.returncode == 0:
            elapsed = time.time() - start_time
            print(f"wait-for-url.py: {url} is available after {elapsed:.0f} seconds")
            break

        print(".", end="", flush=True)
        time.sleep(interval)
    else:
        print()
        print(f"wait-for-it.py: {url} failed to become available")
        sys.exit(1)

    sys.exit(0)
