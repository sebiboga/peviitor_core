# solr-tag-updater

Tool pentru a adăuga `tags` (skills/technologii) în documentele din Solr, folosind Groq pentru extragerea tag-urilor din job title + description.

## Build

```bash
docker build -t peviitor-solr-tag-updater .

docker run --rm -v "C:\peviitor\log:/log" ^
  -e SOLR_CORE_URL="http://host.docker.internal:8983/solr/job" ^
  -e BATCH_SIZE=100 ^
  -e MAX_DOCS=0 ^
  -e GROQ_API_KEY="..." ^
  peviitor-solr-tag-updater



docker run --rm -v "$HOME/peviitor/log:/log" \
  -e SOLR_CORE_URL="http://host.docker.internal:8983/solr/job" \
  -e BATCH_SIZE=100 \
  -e MAX_DOCS=0 \
  -e GROQ_API_KEY="..." \
  peviitor-solr-tag-updater

```