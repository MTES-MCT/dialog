#!/bin/bash
set -eux

DRIVE_ID=184671

ARCHIVE_ID=$(
    curl -L \
        -X POST \
        -H "Authorization: Bearer ${KDRIVE_TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{\"file_ids\": [\"${KDRIVE_FILE_ID}\"]}" \
        "https://api.infomaniak.com/3/drive/${DRIVE_ID}/files/archives" \
        | jq --raw-output .data.uuid
)

curl -L \
    -H "Authorization: Bearer ${KDRIVE_TOKEN}" \
    "https://api.infomaniak.com/2/drive/${DRIVE_ID}/files/archives/${ARCHIVE_ID}" \
    > $1
