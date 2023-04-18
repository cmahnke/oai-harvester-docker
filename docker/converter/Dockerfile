# syntax=docker/dockerfile:experimental

FROM ghcr.io/cmahnke/oai-harvester-docker/harvester:latest

LABEL maintainer="cmahnke@gmail.com"
LABEL org.opencontainers.image.source=https://github.com/cmahnke/oai-harvester-docker

ENV REQ_BUILD="" \
    REQ_RUN="busybox openjdk17-jre jq sed curl" \
    SAXON_URL="https://repo1.maven.org/maven2/net/sf/saxon/Saxon-HE/12.1/Saxon-HE-12.1.jar" \
    RESOLVER_URL="https://repo1.maven.org/maven2/org/xmlresolver/xmlresolver/5.1.2/xmlresolver-4.1.2.jar" \
    SAXON_DIR="/opt/saxon" \
    XSLT_DIR="/opt/xslt" \
    DATA_DIR="/data"

RUN --mount=target=/mnt/build-context \
# Prepare to install dependencies
    apk --update upgrade && \
    apk add --no-cache $REQ_BUILD $REQ_RUN && \
    mkdir -p $SAXON_DIR $DATA_DIR $XSLT_DIR && \
# Get Saxon
    SAXON_FILE=$(basename $SAXON_URL) && \
    RESOLVER_FILE=$(basename $RESOLVER_URL) && \
    curl $SAXON_URL --output $SAXON_DIR/$SAXON_FILE && \
    curl $RESOLVER_URL --output $SAXON_DIR/$RESOLVER_FILE && \
    ln -s $SAXON_DIR/$SAXON_FILE $SAXON_DIR/saxon.jar && \
    ln -s $SAXON_DIR/$RESOLVER_FILE $SAXON_DIR/xmlresolver.jar && \
# Copy files
    cp -r /mnt/build-context/docker/converter/xslt/* $XSLT_DIR && \
    cp /mnt/build-context/docker/converter/scripts/cleanup.sh /usr/local/sbin && \
# Copy entrypoint
    cp /mnt/build-context/docker/converter/entrypoint.d/entrypoint.sh / && \
# Cleanup
    rm -rf /usr/local/bin/composer $BUILD_DIR /root/.composer && \
    apk del ${REQ_BUILD}

VOLUME $DATA_DIR
ENTRYPOINT ["/entrypoint.sh"]