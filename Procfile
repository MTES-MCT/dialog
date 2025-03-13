web: bin/run
worker: bash -c "set -e; while true ; do php -d memory_limit=512M bin/console messenger:consume async --memory-limit=512M --limit=50 ; done"
postdeploy: make scalingo-postdeploy
