##########
# Assets #
##########

# Frontend code builder
FROM node:24-alpine AS frontend-build
WORKDIR /application

COPY package.json yarn.lock ./
COPY webpack* ./
COPY tsconfig.json ./
COPY assets ./assets
COPY public ./public

RUN yarn install; \
    yarn build

# Actual deployable image
FROM nginx:stable AS frontend-deployment
COPY --from=frontend-build /application/public/build /usr/share/nginx/html/build

###############
# PHP Backend #
###############

FROM phpdockerio/php:8.5-fpm AS backend-deployment

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.5-sqlite \
        php8.5-mysql \
        php8.5-intl \
        wget \
        tar \
        patchelf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/log/* /var/cache/* /usr/share/doc/*

WORKDIR /application/libcurl-impersonate

RUN wget -O libcurl-impersonate.tar.gz https://github.com/lexiforest/curl-impersonate/releases/download/v2.3.0/libcurl-impersonate-v2.3.0.x86_64-linux-gnu.tar.gz \
    && echo "b287a1ffc2a5add27fa54f724b9915caaf79a1d965a1e730ebed4b4528a86cf2 libcurl-impersonate.tar.gz" > checksum.sha256 \
    && sha256sum --check checksum.sha256 || exit 1 \
    && tar -xf libcurl-impersonate.tar.gz \
    && patchelf --set-soname libcurl.so.4 /application/libcurl-impersonate/libcurl-impersonate.so

ENV LD_PRELOAD=/application/libcurl-impersonate/libcurl-impersonate.so
ENV CURL_IMPERSONATE=chrome152
ENV APP_ENV=prod
ENV APP_SECRET=""

WORKDIR /application
COPY bin/console      ./bin/
COPY composer.*       ./
COPY .env.dist        ./.env
COPY config           ./config
COPY public/index.php ./public/
COPY src              ./src
COPY templates        ./templates
COPY --from=frontend-build /application/public/build ./public/build

RUN composer install --no-dev --no-scripts; \
    composer clear-cache; \
    composer dump-autoload --optimize --classmap-authoritative; \
    touch ./.env; \
    bin/console cache:warmup; \
    chown -R www-data:www-data ./var/
