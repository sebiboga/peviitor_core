docker build -t peviitor-solr-url-validator .

Windows:
docker run --rm -v "C:\peviitor/log:/log" -e SOLR_CORE_URL="http://host.docker.internal:8983/solr/job" -e BATCH_SIZE=200 -e MAX_DOCS=0 peviitor-solr-url-validator


Ubuntu:
docker run --rm -v "$HOME/peviitor/log:/log" -e SOLR_CORE_URL="http://host.docker.internal:8983/solr/job" -e BATCH_SIZE=200 -e MAX_DOCS=0 peviitor-solr-url-validator

