#!/usr/bin/env bash
set -e

SOLR_URL="http://localhost:8983/solr"

echo "=== Setting 'url' as uniqueKey for 'job' core ==="

echo "=== Waiting for Solr to be up ==="
for i in {1..30}; do
  if curl -s "$SOLR_URL/job/admin/ping" >/dev/null 2>&1; then
    echo "Solr job core is UP (attempt $i)."
    break
  fi
  echo "Solr not ready yet (attempt $i), waiting 2s..."
  sleep 2
done

if ! curl -s "$SOLR_URL/job/admin/ping" >/dev/null 2>&1; then
  echo "ERROR: Solr job core not reachable after 60s. Exiting."
  exit 1
fi

sleep 5

echo "=== Ensuring 'url' field exists with indexed=true ==="
curl -s -X POST "$SOLR_URL/job/schema" \
  -H "Content-Type: application/json" \
  -d '{
  "add-field": {
    "name": "url",
    "type": "string",
    "stored": true,
    "indexed": true,
    "required": true,
    "multiValued": false
  }
}' || true

echo "=== Removing default 'id' field as uniqueKey ==="
curl -s -X POST "$SOLR_URL/job/schema" \
  -H "Content-Type: application/json" \
  -d '{
  "delete-field": {
    "name": "id"
  }
}' || true

echo "=== Setting 'url' as uniqueKey ==="
curl -s -X POST "$SOLR_URL/job/schema/uniquekey" \
  -H "Content-Type: application/json" \
  -d '{
  "uniqueKey": "url"
}'

echo "=== Verifying uniqueKey ==="
curl -s "$SOLR_URL/job/schema/uniquekey" | python3 -m json.tool || true

echo ""
echo "=== DONE: 'url' is now the uniqueKey for 'job' core ==="
