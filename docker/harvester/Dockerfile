# syntax=docker/dockerfile:experimental

FROM php:8.3-cli-alpine

LABEL maintainer="cmahnke@gmail.com"
LABEL org.opencontainers.image.source=https://github.com/cmahnke/oai-harvester-docker

ENV REQ_BUILD="" \
    REQ_RUN="busybox sed bash" \
    BUILD_DIR="/tmp/build" \
    DATA_DIR="/data" \
    HARVESTER_HOME=/opt/harvester

RUN --mount=target=/mnt/build-context \
# Prepare to install dependencies
    apk --update upgrade && \
    apk add --no-cache $REQ_BUILD $REQ_RUN && \
    mkdir -p $BUILD_DIR $DATA_DIR /opt && \
    cd $BUILD_DIR && \
# Get Composer
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
# Copy files
    cp -r /mnt/build-context/* . && \
    cd harvester && \
    COMPOSER_ALLOW_SUPERUSER=1 php /usr/local/bin/composer install --no-dev --no-scripts --no-interaction && \
    cd .. && \
    mv harvester $HARVESTER_HOME && \
     cp docker/harvester/entrypoint.d/entrypoint.sh / && \
# Cleanup
    rm -rf /usr/local/bin/composer $BUILD_DIR /root/.composer && \
    apk del ${REQ_BUILD}

VOLUME $DATA_DIR
ENTRYPOINT ["/entrypoint.sh"]
