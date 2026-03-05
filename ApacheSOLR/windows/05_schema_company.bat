@echo off
setlocal

REM URL de baza pentru core-ul company
set SOLR_URL=http://localhost:8983/solr/company

echo === Add fields ===

REM company
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"company\",\"type\":\"string\",\"stored\":true,\"indexed\":true}}"

REM status
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"status\",\"type\":\"string\",\"stored\":true,\"indexed\":true}}"

REM location
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"location\",\"type\":\"text_general\",\"stored\":true,\"indexed\":true}}"

REM website (multivalued)
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"website\",\"type\":\"string\",\"stored\":true,\"indexed\":true,\"multiValued\":true}}"

REM career (multivalued)
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"career\",\"type\":\"string\",\"stored\":true,\"indexed\":true,\"multiValued\":true}}"

REM brand
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"brand\",\"type\":\"string\",\"stored\":true,\"indexed\":true}}"

REM group
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"group\",\"type\":\"string\",\"stored\":true,\"indexed\":true}}"

REM lastScraped
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"lastScraped\",\"type\":\"string\",\"stored\":true,\"indexed\":false}}"

REM scraperFile
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-field\":{\"name\":\"scraperFile\",\"type\":\"string\",\"stored\":true,\"indexed\":false}}"

echo.
echo === Add copyFields into _text_ ===

REM company -> _text_
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-copy-field\":{\"source\":\"company\",\"dest\":\"_text_\"}}"

REM location -> _text_
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-copy-field\":{\"source\":\"location\",\"dest\":\"_text_\"}}"

REM website -> _text_
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-copy-field\":{\"source\":\"website\",\"dest\":\"_text_\"}}"

REM brand -> _text_
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-copy-field\":{\"source\":\"brand\",\"dest\":\"_text_\"}}"

REM group -> _text_
curl -s -X POST "%SOLR_URL%/schema" ^
 -H "Content-Type: application/json" ^
 -d "{\"add-copy-field\":{\"source\":\"group\",\"dest\":\"_text_\"}}"

echo.
echo === DONE ===
endlocal
