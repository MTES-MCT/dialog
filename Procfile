web: bin/run
worker: bash -c "set -e; while true ; do php -d memory_limit=1G bin/console messenger:consume async --limit=50 ; done"
postdeploy: make scalingo-postdeploy
