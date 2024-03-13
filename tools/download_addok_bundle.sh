#!/bin/bash
set -eux pipefail

DRIVE_ID=184671

ARCHIVE_ID=$(
    curl --fail-with-body -q -L \
        -X POST \
        -H "Authorization: Bearer ${KDRIVE_TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{\"file_ids\": [\"${KDRIVE_FILE_ID}\"]}" \
        "https://api.infomaniak.com/3/drive/${DRIVE_ID}/files/archives" \
    | jq --raw-output '.data.uuid // empty'
)

if [ -z $ARCHIVE_ID ]; then
    echo 'ERROR: Failed to get archive ID, see error output above'
    exit 1
fi

curl -L \
    -H "Authorization: Bearer ${KDRIVE_TOKEN}" \
    "https://api.infomaniak.com/2/drive/${DRIVE_ID}/files/archives/${ARCHIVE_ID}" \
    > $1
