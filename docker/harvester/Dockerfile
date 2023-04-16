# syntax=docker/dockerfile:experimental

FROM php:8.2-cli-alpine

LABEL maintainer="cmahnke@gmail.com"

ENV REQ_BUILD="" \
    REQ_RUN="busybox sed" \
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
    php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
# Copy files
    cp -r /mnt/build-context/* . && \
    cd harvester && \
    COMPOSER_ALLOW_SUPERUSER=1 php /usr/local/bin/composer install && \
    cd .. && \
    mv harvester $HARVESTER_HOME && \
     cp docker/harvester/entrypoint.d/entrypoint.sh / && \
# Cleanup
    rm -rf /usr/local/bin/composer $BUILD_DIR /root/.composer && \
    apk del ${REQ_BUILD}

VOLUME $DATA_DIR
ENTRYPOINT ["/entrypoint.sh"]
