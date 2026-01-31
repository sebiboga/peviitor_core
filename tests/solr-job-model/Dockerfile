FROM php:8.3-cli

WORKDIR /app

COPY config.php testplan.php runner.php report.php run.php index.php entrypoint.sh ./
COPY tests ./tests

ENV SOLR_URL=http://host.docker.internal:8983/solr/job/select
ENV SOLR_USER=solr
ENV SOLR_PASS=SolrRocks
ENV REPORT_FILE=/app/report.html
ENV STATUS_FILE=/app/status.json

RUN chmod +x entrypoint.sh

EXPOSE 8900

CMD ["./entrypoint.sh"]
