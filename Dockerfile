# syntax=docker.io/docker/dockerfile:1.13-labs
# KaNeil Production Dockerfile

##
#  If you want to build this locally you want to run `docker build -f Dockerfile.dev .`
##

# ================================
# Stage 1-1: Composer Install
# ================================
FROM --platform=$TARGETOS/$TARGETARCH localhost:5000/base-php:$TARGETARCH AS composer

WORKDIR /build

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy bare minimum to install Composer dependencies
COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# ================================
# Stage 1-2: Yarn Install
# ================================
FROM --platform=$TARGETOS/$TARGETARCH node:20-alpine AS yarn

WORKDIR /build

# Copy bare minimum to install Yarn dependencies
COPY package.json yarn.lock ./

RUN yarn config set network-timeout 300000 \
    && yarn install --frozen-lockfile

# ================================
# Stage 2-1: Composer Optimize
# ================================
FROM --platform=$TARGETOS/$TARGETARCH composer AS composerbuild

# Copy full code to optimize autoload
COPY --exclude=docker/ . ./

RUN composer dump-autoload --optimize

# ================================
# Stage 2-2: Build Frontend Assets
# ================================
FROM --platform=$TARGETOS/$TARGETARCH yarn AS yarnbuild

WORKDIR /build

# Copy full code
COPY --exclude=docker/ . ./
COPY --from=composer /build .

RUN yarn run build

# ================================
# Stage 5: Build Final Application Image
# ================================
FROM --platform=$TARGETOS/$TARGETARCH localhost:5000/base-php:$TARGETARCH AS final

WORKDIR /var/www/html

RUN apk add --no-cache \
    # packages for running the panel
    caddy ca-certificates supervisor supercronic fcgi \
    # required for installing plugins. Pulled from https://github.com/kaneil-dev/panel/pull/2034
    zip unzip 7zip bzip2-dev yarn git

# Copy composer binary for runtime plugin dependency management
COPY --from=composer /usr/local/bin/composer /usr/local/bin/composer
COPY --chown=root:www-data --chmod=770 --from=composerbuild /build .
COPY --chown=root:www-data --chmod=770 --from=yarnbuild /build/public ./public

# Create and remove directories
RUN mkdir -p /kaneil-data/storage /kaneil-data/plugins /var/run/supervisord \
    && rm -rf /var/www/html/plugins \
# Symlinks for env, database, storage, and plugins
    && ln -s  /kaneil-data/.env /var/www/html/.env \
    && ln -s  /kaneil-data/database/database.sqlite ./database/database.sqlite \
    && ln -s  /kaneil-data/storage /var/www/html/public/storage \
    && ln -s  /kaneil-data/storage /var/www/html/storage/app/public \
    && ln -s  /kaneil-data/plugins /var/www/html \
# Allow www-data write permissions where necessary
    && chown -R www-data: /kaneil-data .env ./storage ./plugins ./bootstrap/cache /var/run/supervisord /var/www/html/public/storage \
    && chmod -R 770 /kaneil-data ./storage ./bootstrap/cache /var/run/supervisord \
    && chown -R www-data: /usr/local/etc/php/ /usr/local/etc/php-fpm.d/ /var/www/html/composer.json /var/www/html/composer.lock
# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
# Add Laravel scheduler to crontab
COPY docker/crontab /etc/crontabs/crontab

COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/healthcheck.sh /healthcheck.sh

HEALTHCHECK --interval=5m --timeout=10s --start-period=5s --retries=3 \
  CMD /bin/ash /healthcheck.sh

EXPOSE 80 443

VOLUME /kaneil-data

USER www-data

ENTRYPOINT [ "/bin/ash", "/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
