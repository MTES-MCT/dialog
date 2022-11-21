#!/bin/bash

echo "Executing migration..."
php bin/console doctrine:migrations:migrate --no-interaction
