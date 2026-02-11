#!/bin/bash

set -e

# Configuration
SCALINGO_APP="${1:-dialog}"
DUMP_DIR="${2:-.}"
PORT=10000
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DUMP_FILE="${DUMP_DIR}/dialog_dump_${TIMESTAMP}.sql"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Récupération du dump PostgreSQL de Scalingo...${NC}"
echo -e "${BLUE}Application: ${SCALINGO_APP}${NC}"
echo -e "${BLUE}Fichier de sortie: ${DUMP_FILE}${NC}"

# Ensure Scalingo CLI is authenticated
if ! scalingo whoami > /dev/null 2>&1; then
    echo -e "${BLUE}Authentification Scalingo requise...${NC}"
    scalingo login --ssh
fi

# Create directory if it doesn't exist
mkdir -p "${DUMP_DIR}"

# Get database credentials
echo -e "${BLUE}Récupération des identifiants de la base de données...${NC}"
DATABASE_URL=$(scalingo --app "${SCALINGO_APP}" env-get DATABASE_URL)

# Parse PostgreSQL connection string
# Format: postgresql://user:password@host:port/dbname
DB_USER=$(echo "$DATABASE_URL" | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
DB_PASSWORD=$(echo "$DATABASE_URL" | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
DB_NAME=$(echo "$DATABASE_URL" | sed -n 's/.*\/\([^?]*\).*/\1/p')

# Start the tunnel in the background
echo -e "${BLUE}Création du tunnel Scalingo...${NC}"
./tools/scalingodbtunnel "${SCALINGO_APP}" --port "${PORT}" --host-url > /tmp/scalingo_tunnel_url.txt &
TUNNEL_PID=$!

# Wait for tunnel to be ready
sleep 2

# Read the tunnel URL
TUNNEL_URL=$(cat /tmp/scalingo_tunnel_url.txt)
echo -e "${BLUE}Tunnel établi: ${TUNNEL_URL}${NC}"

# Extract host and port from the tunnel URL
TUNNEL_HOST=$(echo "$TUNNEL_URL" | sed -n 's/.*@\([^:]*\):.*/\1/p')
TUNNEL_PORT=$(echo "$TUNNEL_URL" | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')

# Perform the pg_dump
echo -e "${BLUE}Exécution du pg_dump...${NC}"

PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h "${TUNNEL_HOST}" \
    -p "${TUNNEL_PORT}" \
    -U "${DB_USER}" \
    -d "${DB_NAME}" \
    --verbose \
    > "${DUMP_FILE}"

EXIT_CODE=$?

# Stop the tunnel
kill $TUNNEL_PID 2>/dev/null || true
wait $TUNNEL_PID 2>/dev/null || true
rm -f /tmp/scalingo_tunnel_url.txt

if [ $EXIT_CODE -eq 0 ]; then
    DUMP_SIZE=$(du -h "${DUMP_FILE}" | cut -f1)
    echo -e "${GREEN}✓ Dump récupéré avec succès!${NC}"
    echo -e "${GREEN}Fichier: ${DUMP_FILE}${NC}"
    echo -e "${GREEN}Taille: ${DUMP_SIZE}${NC}"
else
    rm -f "${DUMP_FILE}"
    echo -e "${RED}✗ Erreur lors de la récupération du dump${NC}"
    exit 1
fi

# Compression option
if [ "${3:-}" = "--compress" ] || [ "${3:-}" = "-z" ]; then
    echo -e "${BLUE}Compression du dump...${NC}"
    gzip "${DUMP_FILE}"
    echo -e "${GREEN}✓ Dump compressé: ${DUMP_FILE}.gz${NC}"
fi
