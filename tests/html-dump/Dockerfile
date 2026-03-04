FROM php:8.2-cli

# Install Chromium and deps
RUN apt-get update && apt-get install -y \
    chromium \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Symlink "chrome" -> "chromium" ca să avem nume scurt
RUN ln -s /usr/bin/chromium /usr/local/bin/chrome

WORKDIR /app

COPY config.php solr_helpers.php run.php ./

ENTRYPOINT ["php", "run.php"]
