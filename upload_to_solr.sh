#!/bin/bash

CSV_FILE="$1"
SOLR_URL="${2:-$SOLR_URL}"
SOLR_USER="${3:-$SOLR_USER}"
SOLR_PASS="${4:-$SOLR_PASS}"
BATCH_SIZE="${5:-50}"

AUTH="$SOLR_USER:$SOLR_PASS"

echo "=========================================="
echo "Processing: $CSV_FILE"
echo "=========================================="

if [ ! -f "$CSV_FILE" ]; then
    echo "Error: File not found"
    exit 1
fi

TOTAL=$(tail -n +2 "$CSV_FILE" | wc -l)
echo "Total companies: $TOTAL"

count=0
success=0
errors=0

# Process line by line into JSON
tail -n +2 "$CSV_FILE" | while IFS= read -r line; do
    DENUMIRE=$(echo "$line" | awk -F'^' '{print $1}')
    CUI=$(echo "$line" | awk -F'^' '{print $2}')
    JUDET=$(echo "$line" | awk -F'^' '{print $3}')
    ADRESA=$(echo "$line" | awk -F'^' '{print $4}')

    DENUMIRE_ESC=$(echo "$DENUMIRE" | sed 's/\\/\\\\/g; s/"/\\"/g')
    JUDET_ESC=$(echo "$JUDET" | sed 's/\\/\\\\/g; s/"/\\"/g')
    ADRESA_ESC=$(echo "$ADRESA" | sed 's/\\/\\\\/g; s/"/\\"/g')

    echo "{\"id\":\"$CUI\",\"company\":{\"set\":\"$DENUMIRE_ESC\"},\"county\":{\"set\":\"$JUDET_ESC\"},\"address\":{\"set\":\"$ADRESA_ESC\"},\"status\":{\"set\":\"activ\"}}"
    ((count++))
done > /tmp/batch.json

# Split and send
split -l $BATCH_SIZE /tmp/batch.json /tmp/solr_batch_

for file in /tmp/solr_batch_*; do
    echo "[" > "$file.json"
    paste -sd',' "$file" >> "$file.json"
    echo "]" >> "$file.json"
    
    curl -s -X POST "$SOLR_URL/update?commit=true" \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d @"$file.json" | grep -q '"status":0' && ((success+=BATCH_SIZE)) || ((errors+=BATCH_SIZE))
    
    rm "$file" "$file.json"
done

echo "Processed: $count | Success: $success | Errors: $errors"
rm -f /tmp/batch.json /tmp/solr_batch_*
