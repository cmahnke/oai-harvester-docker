#!/bin/sh
HARVESTER_CONF=$HARVESTER_HOME/config.yaml

if [ -n "$@" ] ; then
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
else
    echo "$HARVESTER_CONF isn't writeable, might be mounted"
fi

cd $HARVESTER_HOME
php src/harvester.php