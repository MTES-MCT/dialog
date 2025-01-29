web: bin/run
worker: php bin/console messenger:consume async --time-limit=300 --memory-limit=256M --limit=50
postdeploy: make scalingo-postdeploy
