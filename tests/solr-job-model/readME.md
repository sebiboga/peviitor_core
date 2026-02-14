 docker build -t peviitor-job-tests .
 docker run --rm -e SOLR_URL="http://host.docker.internal:8983/solr/job/select" -p 8900:8900 peviitor-job-tests