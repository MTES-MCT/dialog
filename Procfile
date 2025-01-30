web: bin/run
worker: bash -c "set -e; while true ; do php bin/console messenger:consume async --memory-limit=256M --limit=50 ; done"
postdeploy: make scalingo-postdeploy
