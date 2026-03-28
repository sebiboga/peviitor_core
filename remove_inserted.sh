#!/bin/bash

CSV_FILE="${1:-Requirements/od_firme_active.csv}"
SOLR_URL="${2:-https://solr.peviitor.ro/solr/company}"
AUTH="${3:-solr:SolrRocks}"
OUTPUT_FILE="${CSV_FILE}.new"

echo "Getting inserted IDs from SOLR..."
curl -s "${SOLR_URL}/select?q=*:*&fl=id&rows=100000&wt=csv" -u "$AUTH" | tail -n +2 > /tmp/solr_ids.txt

INSERTED_COUNT=$(wc -l < /tmp/solr_ids.txt)
echo "Found $INSERTED_COUNT inserted IDs in SOLR"

ORIGINAL_COUNT=$(tail -n +2 "$CSV_FILE" | wc -l)
echo "CSV has $ORIGINAL_COUNT companies"

echo "Removing inserted companies from CSV..."
echo "DENUMIRE^CUI^ADR_JUDET^ADRESA" > "$OUTPUT_FILE"

awk -F'^' -v ids=/tmp/solr_ids.txt '
BEGIN { while ((getline < ids) > 0) inserted[$0] = 1 }
NR > 1 && !($2 in inserted) { print }
' "$CSV_FILE" >> "$OUTPUT_FILE"

REMAINING=$(tail -n +2 "$OUTPUT_FILE" | wc -l)

echo ""
echo "Results:"
echo "  Original: $ORIGINAL_COUNT"
echo "  Inserted (skipped): $INSERTED_COUNT"
echo "  Remaining: $REMAINING"

# Backup and replace
if [ $REMAINING -gt 0 ]; then
    mv "$CSV_FILE" "${CSV_FILE}.bak"
    mv "$OUTPUT_FILE" "$CSV_FILE"
    echo ""
    echo "Done! Backup: ${CSV_FILE}.bak"
else
    echo "Warning: No companies remaining!"
    rm -f "$OUTPUT_FILE"
fi

rm -f /tmp/solr_ids.txt
