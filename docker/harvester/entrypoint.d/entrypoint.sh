#!/bin/sh

echo "Starting Harvester container"

HARVESTER_CONF=$HARVESTER_HOME/config.yaml

if [ $# -ne 0 ] ; then
    echo "Running provided command '$@'"
    $@
    exit 0
fi

if [ -w  "$HARVESTER_CONF" ] ; then
    if [ -n "$OAI_URL" ] ; then
        OAI_URL_ESCAPED=$(echo $OAI_URL | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/oai_url: .*/oai_url: $OAI_URL_ESCAPED/g" "$HARVESTER_CONF"
    fi

    if [ -n "$METADATA_PREFIX" ] ; then
        METADATA_PREFIX_ESCAPED=$(echo $METADATA_PREFIX_URL | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/metadata_prefix: .*/metadata_prefix: $METADATA_PREFIX_ESCAPED/g" "$HARVESTER_CONF"
    fi

    if [ -n "$SET" ] ; then
        SET_ESCAPED=$(echo $SET | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/set: .*/set: $SET_ESCAPED/g" "$HARVESTER_CONF"
    fi

    if [ -n "$TARGET_DIR" ] ; then
        TARGET_DIR_ESCAPED=$(echo $TARGET_DIR | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/target_dir: .*/target_dir: $TARGET_DIR_ESCAPED/g" "$HARVESTER_CONF"
    fi

    if [ -n "$USER" ] ; then
        USER_ESCAPED=$(echo $USER | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/user: .*/user: $USER_ESCAPED/g" "$HARVESTER_CONF"
    fi

    if [ -n "$PASS" ] ; then
        PASS_ESCAPED=$(echo $PASS | sed -e 's/\\/\\\\/g; s/\//\\\//g; s/&/\\\&/g')
        sed -i "s/pass: .*/pass: $PASS_ESCAPED/g" "$HARVESTER_CONF"
    fi
else
    echo "$HARVESTER_CONF isn't writeable, might be mounted"
fi

cd $HARVESTER_HOME
php src/harvester.php