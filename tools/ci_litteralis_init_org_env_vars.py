#!/usr/bin/env python3
import argparse
import os
import sys
import json
import traceback

from fnmatch import fnmatch


def main(secrets_env: str, env_pattern: str) -> int:
    gha_secrets = os.environ.get(secrets_env)

    if not gha_secrets:
        print(f"Environment variable {secrets_env} is missing or empty", file=sys.stderr)
        return 1

    try:
        secrets = json.loads(gha_secrets)
    except json.JSONDecodeError:
        traceback.print_exc()
        return 1

    org_secrets = {}

    for name, value in secrets.items():
        if not fnmatch(name, env_pattern):
            continue

        org_secrets[name] = value

    with open(".env.local", "a") as f:
        for name, value in org_secrets.items():
            f.write(f"{name}={value}\n")

    return 0


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "secrets_env",
        help="Name of environment variable containing JSON dump of GitHub Actions secrets",
    )
    parser.add_argument(
        "env_pattern", help="Glob pattern for secrets to add to .env.local"
    )
    args = parser.parse_args()

    sys.exit(
        main(
            secrets_env=args.secrets_env,
            env_pattern=args.env_pattern,
        )
    )
