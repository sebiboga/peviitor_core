#!/bin/bash

CSV_FILE="${1:-Requirements/od_firme_active.csv}"
SOLR_URL="${2:-https://solr.peviitor.ro/solr/company}"
AUTH="${3:-solr:SolrRocks}"
BATCH_SIZE="${4:-50}"
LIMIT="${5:-100}"

RED='\033[0;31m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

echo "=========================================="
echo "  SOLR Company Updater"
echo "=========================================="
echo ""

if [ ! -f "$CSV_FILE" ]; then
    echo "Error: $CSV_FILE not found!"
    exit 1
fi

ORIGINAL_COUNT=$(tail -n +2 "$CSV_FILE" | wc -l)
echo "CSV file: $CSV_FILE"
echo "Total companies: $ORIGINAL_COUNT"
echo "Limit: $LIMIT | Batch size: $BATCH_SIZE"
echo ""

# Get last N lines
tail -n +2 "$CSV_FILE" | tail -n $LIMIT > /tmp/csv_slice.txt
count=$(wc -l < /tmp/csv_slice.txt)
echo "Processing $count companies..."
echo ""

success=0
errors=0
batch_num=0

# Process line by line
while IFS= read -r line; do
    DENUMIRE=$(echo "$line" | awk -F'^' '{print $1}')
    CUI=$(echo "$line" | awk -F'^' '{print $2}')
    JUDET=$(echo "$line" | awk -F'^' '{print $3}')
    ADRESA=$(echo "$line" | awk -F'^' '{print $4}')

    DENUMIRE_ESC=$(echo "$DENUMIRE" | sed 's/\\/\\\\/g; s/"/\\"/g')
    JUDET_ESC=$(echo "$JUDET" | sed 's/\\/\\\\/g; s/"/\\"/g')
    ADRESA_ESC=$(echo "$ADRESA" | sed 's/\\/\\\\/g; s/"/\\"/g')

    echo "{\"id\":\"$CUI\",\"company\":{\"set\":\"$DENUMIRE_ESC\"},\"county\":{\"set\":\"$JUDET_ESC\"},\"address\":{\"set\":\"$ADRESA_ESC\"},\"status\":{\"set\":\"activ\"}}"
done < /tmp/csv_slice.txt > /tmp/batch.json

echo "Batch built. Sending..."

# Split into batches and send
split -l $BATCH_SIZE /tmp/batch.json /tmp/solr_batch_

for file in /tmp/solr_batch_*; do
    ((batch_num++))
    
    echo "[" > "$file.json"
    paste -sd',' "$file" >> "$file.json"
    echo "]" >> "$file.json"
    
    response=$(curl -s -X POST "$SOLR_URL/update?commit=true" \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d @"$file.json")
    
    batch_size=$(wc -l < "$file")
    
    if echo "$response" | grep -q '"status":0'; then
        ((success+=batch_size))
    else
        ((errors+=batch_size))
        echo "Batch $batch_num failed"
    fi
    
    echo -ne "\rBatch $batch_num | Success: $success | Errors: $errors   "
done

echo ""
echo ""
echo "=========================================="
echo "Completed at: $(date)"
echo "=========================================="
echo "Processed: $count"
echo "Success: $success | Errors: $errors"
echo ""

# Remove from CSV
remain=$((ORIGINAL_COUNT - LIMIT))
if [ $remain -gt 0 ]; then
    head -n $((ORIGINAL_COUNT - LIMIT + 1)) "$CSV_FILE" > "${CSV_FILE}.tmp"
    mv "${CSV_FILE}.tmp" "$CSV_FILE"
fi

echo "Remaining in CSV: $remain"
echo "=========================================="

rm -f /tmp/csv_slice.txt /tmp/batch.json /tmp/solr_batch_* /tmp/solr_batch_*.json
