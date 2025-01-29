web: bin/run
worker: bash -c "while true ; do php bin/console messenger:consume async --memory-limit=256M --limit=50 ; if [ $? -ne 0 ]; then break fi ; done"
postdeploy: make scalingo-postdeploy
