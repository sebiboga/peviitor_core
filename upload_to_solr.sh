#!/bin/bash

CSV_FILE="$1"
SOLR_URL="${2:-$SOLR_URL}"
SOLR_USER="${3:-$SOLR_USER}"
SOLR_PASS="${4:-$SOLR_PASSWD}"
BATCH_SIZE="${5:-50}"

AUTH="$SOLR_USER:$SOLR_PASS"

echo "=========================================="
echo "SOLR Upload Script"
echo "=========================================="
echo "CSV: $CSV_FILE"
echo "SOLR URL: $SOLR_URL"
echo ""

# Test SOLR connection first
echo "Testing SOLR connection..."
TEST=$(curl -s -u "$AUTH" "$SOLR_URL/admin/ping" | grep -o "status" | head -1)
if [ -z "$TEST" ]; then
    echo "ERROR: Cannot connect to SOLR at $SOLR_URL"
    echo "Please check SOLR_URL, SOLR_USER, and SOLR_PASSWD"
    exit 1
fi
echo "SOLR connection OK!"
echo ""

if [ ! -f "$CSV_FILE" ]; then
    echo "ERROR: File not found: $CSV_FILE"
    exit 1
fi

TOTAL=$(tail -n +2 "$CSV_FILE" | wc -l)
echo "Total companies to upload: $TOTAL"
echo ""

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

echo "Building batches of $BATCH_SIZE..."

# Split and send
split -l $BATCH_SIZE /tmp/batch.json /tmp/solr_batch_

for file in /tmp/solr_batch_*; do
    echo "[" > "$file.json"
    paste -sd',' "$file" >> "$file.json"
    echo "]" >> "$file.json"
    
    RESULT=$(curl -s -w "%{http_code}" -X POST "$SOLR_URL/update?commit=true" \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d @"$file.json")
    
    if echo "$RESULT" | grep -q "200\|204"; then
        ((success+=BATCH_SIZE))
        echo -ne "\rProgress: $success / $count uploaded    "
    else
        ((errors+=BATCH_SIZE))
        echo ""
        echo "ERROR: Batch failed with code $RESULT"
    fi
    
    rm "$file" "$file.json"
done

echo ""
echo ""
echo "=========================================="
echo "DONE"
echo "=========================================="
echo "Total: $count | Success: $success | Errors: $errors"
echo "=========================================="

rm -f /tmp/batch.json /tmp/solr_batch_*
