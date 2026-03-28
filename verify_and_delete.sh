#!/bin/bash

CHUNK_FILE="$1"
SOLR_URL="${2:-$SOLR_URL}"
SOLR_USER="${3:-$SOLR_USER}"
SOLR_PASS="${4:-$SOLR_PASSWD}"

AUTH="$SOLR_USER:$SOLR_PASS"

echo "=========================================="
echo "Verification Script"
echo "=========================================="
echo "Chunk: $CHUNK_FILE"
echo ""

# Count companies in chunk
EXPECTED=$(tail -n +2 "$CHUNK_FILE" | wc -l)
echo "Expected companies in chunk: $EXPECTED"

# Get current SOLR count
SOLR_BEFORE=$(curl -s -u "$AUTH" "$SOLR_URL/select?q=*:*&rows=0" | grep -o '"numFound":[0-9]*' | grep -o '[0-9]*')
echo "SOLR count before: $SOLR_BEFORE"

# Upload
echo ""
echo "Uploading..."
bash upload_to_solr.sh "$CHUNK_FILE" "$SOLR_URL" "$SOLR_USER" "$SOLR_PASS"

# Get SOLR count after
SOLR_AFTER=$(curl -s -u "$AUTH" "$SOLR_URL/select?q=*:*&rows=0" | grep -o '"numFound":[0-9]*' | grep -o '[0-9]*')
echo ""
echo "SOLR count after: $SOLR_AFTER"

ADDED=$((SOLR_AFTER - SOLR_BEFORE))
echo "Companies added: $ADDED"

# Check if all arrived
if [ "$ADDED" -ge "$EXPECTED" ]; then
    echo ""
    echo "SUCCESS: All $EXPECTED companies uploaded!"
    echo "Deleting chunk file: $CHUNK_FILE"
    rm "$CHUNK_FILE"
    echo "DELETED!"
else
    MISSING=$((EXPECTED - ADDED))
    echo ""
    echo "WARNING: Only $ADDED of $EXPECTED companies uploaded!"
    echo "Missing: $MISSING companies"
    echo "Keeping chunk file for retry."
fi
