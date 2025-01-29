web: bin/run
worker: php bin/console messenger:consume async --memory-limit=256M
postdeploy: make scalingo-postdeploy
