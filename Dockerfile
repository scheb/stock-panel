##########
# Assets #
##########

# Frontend code builder
FROM node:18-alpine AS frontend-build
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

FROM phpdockerio/php:8.3-fpm AS backend-deployment

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.3-sqlite \
        php8.3-mysql \
        php8.3-intl \
        wget \
        tar \
        patchelf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/log/* /var/cache/* /usr/share/doc/*

WORKDIR /application/libcurl-impersonate
RUN wget https://github.com/lwthiker/curl-impersonate/releases/download/v0.6.1/libcurl-impersonate-v0.6.1.x86_64-linux-gnu.tar.gz \
    && tar -xf libcurl-impersonate-v0.6.1.x86_64-linux-gnu.tar.gz \
    && patchelf --set-soname libcurl.so.4 /application/libcurl-impersonate/libcurl-impersonate-chrome.so

ENV LD_PRELOAD=/application/libcurl-impersonate/libcurl-impersonate-chrome.so
ENV CURL_IMPERSONATE=chrome116
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
