FROM php:8.2-cli

WORKDIR /app

# Dependențe de bază + Node.js + librării pentru Chrome headless
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
    libdrm-dev \
    libgbm-dev \
    libglib2.0-0 \
    libgtk-3-0 \
    libnss3 \
    libx11-xcb1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxkbcommon0 \
    libxrandr2 \
    xdg-utils \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY . /app

# Instalăm Puppeteer în container
RUN npm init -y && npm install puppeteer

RUN mkdir -p /log

ENV SOLR_CORE_URL=""
ENV SOLR_USER=""
ENV SOLR_PASS=""
ENV BATCH_SIZE=100
ENV MAX_DOCS=0
ENV TAGS_FIELD="tags"
ENV MAX_TAGS=20
ENV GROQ_API_KEY=""
ENV GROQ_MODEL="groq/compound"
ENV URL_TIMEOUT=10

CMD ["php", "run.php"]
