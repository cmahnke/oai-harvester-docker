#!/bin/sh

rm -rf /usr/local/bin/composer $BUILD_DIR /root/.composer /opt/saxon/ $HARVESTER_HOME
apk del $REQ_RUN $REQ_BUILD

