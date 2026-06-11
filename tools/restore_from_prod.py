#!/usr/bin/env python3
"""Restaure la base d'une app Scalingo (prod) vers une cible (locale ou Scalingo).

Usage typique :

  # 1) En local : télécharger la prod et la restaurer dans Docker (`database`)
  python3 tools/restore_from_prod.py

  # 2) Sur un staging/review-app Scalingo (one-off run)
  python3 tools/restore_from_prod.py --target remote

Pré-requis :
  - CLI `scalingo` installée et authentifiée (login --ssh ou SCALINGO_API_TOKEN).
  - En mode local : `docker compose` actif avec le service `database`.
  - En mode remote : DATABASE_URL fournie par Scalingo, et SCALINGO_API_TOKEN
    si l'app one-off n'a pas l'auth SSH.
"""
from __future__ import annotations

import argparse
import os
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path
from urllib.parse import urlparse


def _run(cmd: list[str], *, check: bool = True, env: dict | None = None, capture: bool = False, retries: int = 0, retry_delay: float = 5.0) -> subprocess.CompletedProcess:
    for attempt in range(retries + 1):
        try:
            return subprocess.run(cmd, check=check, env=env, capture_output=capture, text=capture)
        except subprocess.CalledProcessError:
            if attempt == retries:
                raise
            delay = retry_delay * (2 ** attempt)
            print(f"Échec de la récupération de la base de donnée (tentative {attempt + 1}/{retries + 1}), nouvel essai dans {delay:.0f}s...", file=sys.stderr)
            time.sleep(delay)


def _scalingo_authenticated() -> bool:
    return subprocess.run(["scalingo", "whoami"], capture_output=True).returncode == 0


def _ensure_scalingo_cli() -> None:
    # Les conteneurs Scalingo (postdeploy / one-off) n'ont pas la CLI installée.
    if shutil.which("scalingo"):
        return
    install_dir = Path.home() / ".local" / "bin"
    install_dir.mkdir(parents=True, exist_ok=True)
    print(f"Installation de la CLI scalingo dans {install_dir}...")
    subprocess.run(
        f"curl -fsSL https://cli-dl.scalingo.com/install | bash -s -- "
        f"--install-dir {install_dir} --yes",
        shell=True, check=True,
    )
    os.environ["PATH"] = f"{install_dir}{os.pathsep}{os.environ.get('PATH', '')}"


def _ensure_scalingo_auth() -> None:
    _ensure_scalingo_cli()
    if _scalingo_authenticated():
        return
    if os.environ.get("SCALINGO_API_TOKEN"):
        _run(["scalingo", "login", "--api-token", os.environ["SCALINGO_API_TOKEN"]])
        return
    _run(["scalingo", "login", "--ssh"])


def _resolve_pg_addon(app: str) -> str:
    """Récupère l'ID de l'addon PostgreSQL en parsant la sortie tabulaire du CLI."""
    import re

    result = subprocess.run(
        ["scalingo", "--app", app, "addons"],
        capture_output=True, text=True, check=True,
    )
    for line in result.stdout.splitlines():
        if "postgresql" not in line.lower():
            continue
        match = re.search(r"ad-[0-9a-f-]{30,}", line)
        if match:
            return match.group(0)
    raise RuntimeError(f"Aucun addon PostgreSQL trouvé pour l'app {app}\n{result.stdout}")


def _download_backup(app: str, dest_dir: Path) -> Path:
    addon = _resolve_pg_addon(app)
    archive = dest_dir / "backup.tar.gz"
    # L'API Scalingo peut répondre avec des timeouts transitoires (context deadline
    # exceeded) depuis les conteneurs postdeploy : on réessaie avec backoff.
    _run([
        "scalingo", "--app", app, "--addon", addon,
        "backups-download", "--output", str(archive),
    ], retries=3)
    _run(["tar", "-xzf", str(archive), "-C", str(dest_dir)])
    candidates = list(dest_dir.rglob("*.pgsql")) + list(dest_dir.rglob("*.dump"))
    if not candidates:
        raise RuntimeError("Aucun fichier dump (.pgsql/.dump) trouvé dans l'archive")
    dump = candidates[0]
    print(f"Dump : {dump} ({dump.stat().st_size / 1024 / 1024:.1f} Mo)")
    return dump


def _restore_local(dump: Path, db_name: str = "dialog", db_user: str = "dialog") -> None:
    container_dump = "/tmp/restore_from_prod.dump"
    # Copier le dump dans le conteneur database.
    _run(["docker", "compose", "cp", str(dump), f"database:{container_dump}"])
    # Drop & recreate pour partir d'une base propre (preserve les rôles).
    _run([
        "docker", "compose", "exec", "-T", "database",
        "psql", "-U", db_user, "-d", "postgres",
        "-c", f'DROP DATABASE IF EXISTS "{db_name}";',
    ])
    _run([
        "docker", "compose", "exec", "-T", "database",
        "psql", "-U", db_user, "-d", "postgres",
        "-c", f'CREATE DATABASE "{db_name}" OWNER "{db_user}";',
    ])
    _run([
        "docker", "compose", "exec", "-T", "database",
        "pg_restore",
        "--no-owner", "--no-privileges",
        "--jobs=4",
        "-U", db_user, "-d", db_name,
        container_dump,
    ], check=False)  # pg_restore retourne souvent 1 sur warnings non bloquants
    _run(["docker", "compose", "exec", "-T", "database", "rm", "-f", container_dump])


def _restore_remote(dump: Path, database_url: str) -> None:
    # Sur Scalingo, postgresql-client n'est pas installé par défaut côté one-off ;
    # le buildpack PHP le fournit dans la plupart des cas. Sinon ajouter `postgresql-client` à Aptfile.
    if not shutil.which("pg_restore"):
        raise RuntimeError(
            "pg_restore introuvable. Ajoutez 'postgresql-client' à Aptfile et redéployez."
        )
    # Passer les credentials via l'env (PG*) plutôt qu'en argv pour éviter
    # toute exposition dans les logs ou via `ps`.
    parsed = urlparse(database_url)
    if not parsed.hostname or not parsed.path:
        raise RuntimeError("DATABASE_URL invalide (host/dbname manquant).")
    pg_env = {
        **os.environ,
        "PGHOST": parsed.hostname,
        "PGPORT": str(parsed.port or 5432),
        "PGUSER": parsed.username or "",
        "PGPASSWORD": parsed.password or "",
        "PGDATABASE": parsed.path.lstrip("/"),
    }
    if parsed.scheme.endswith("+ssl") or "sslmode=" not in (parsed.query or ""):
        pg_env.setdefault("PGSSLMODE", "require")
    _run([
        "pg_restore",
        "--clean", "--if-exists",
        "--no-owner", "--no-privileges",
        "--jobs=4",
        "-d", pg_env["PGDATABASE"],
        str(dump),
    ], check=False, env=pg_env)


def _post_restore_local(skip_anonymize: bool) -> None:
    _run(["make", "dbmigrate"])
    if skip_anonymize:
        print("SKIP_ANONYMIZE : étape d'anonymisation ignorée.")
        return
    _run(["make", "console", "CMD=app:db:anonymize --force"])


def _post_restore_remote(skip_anonymize: bool) -> None:
    _run(["php", "bin/console", "doctrine:migrations:migrate", "--no-interaction", "--all-or-nothing"])
    if skip_anonymize:
        print("SKIP_ANONYMIZE : étape d'anonymisation ignorée.")
        return
    _run(["php", "bin/console", "app:db:anonymize", "--force", "--allow-prod-env"])
    _run(["php", "bin/console", "cache:clear", "--no-warmup"], check=False)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument(
        "--source",
        default="dialog",
        help="Nom de l'app Scalingo source (défaut: dialog).",
    )
    parser.add_argument(
        "--target",
        choices=["local", "remote"],
        default="local",
        help="local : restaure dans Docker. remote : restaure dans $DATABASE_URL (one-off Scalingo).",
    )
    parser.add_argument(
        "--skip-anonymize",
        action="store_true",
        help="Ne pas exécuter app:db:anonymize après restore (DANGER RGPD).",
    )
    parser.add_argument(
        "--keep-dump",
        action="store_true",
        help="Conserver le dump téléchargé après exécution.",
    )
    args = parser.parse_args()

    if args.target == "remote":
        current_app = os.environ.get("APP") or os.environ.get("CONTAINER", "")
        if current_app == args.source and os.environ.get("ALLOW_PROD") != "1":
            print(f"Refus : l'app courante ({current_app}) semble être la prod ({args.source}). "
                  "Définissez ALLOW_PROD=1 si c'est volontaire.", file=sys.stderr)
            return 1
        database_url = os.environ.get("DATABASE_URL")
        if not database_url:
            print("DATABASE_URL absent de l'environnement (mode remote).", file=sys.stderr)
            return 1
        # Vérification rapide que l'URL parse.
        urlparse(database_url)

    _ensure_scalingo_auth()

    workdir = Path(tempfile.mkdtemp(prefix="dialog-restore-"))
    try:
        dump = _download_backup(args.source, workdir)

        if args.target == "local":
            _restore_local(dump)
            _post_restore_local(args.skip_anonymize)
        else:
            _restore_remote(dump, os.environ["DATABASE_URL"])
            _post_restore_remote(args.skip_anonymize)
    finally:
        if args.keep_dump:
            print(f"Dump conservé dans : {workdir}")
        else:
            shutil.rmtree(workdir, ignore_errors=True)

    print("\033[1;32m==>\033[0m Restore terminé.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
