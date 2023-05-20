#!/bin/sh

echo "Starting Converter container" 1>&2

if [ $# -ne 0 ] ; then
    echo "Running provided command '$@'" 1>&2
    $@
    exit 0
fi

if [ -x /entrypoint-harvester.sh ] ; then
    /entrypoint-harvester.sh
fi

if [ -r $XSLT_DIR/default.xsl ] ; then
    java -Xmx1024m -cp $SAXON_DIR/saxon.jar:$SAXON_DIR/xmlresolver.jar net.sf.saxon.Transform -xsl:"$XSLT_DIR/default.xsl" -s:"$XSLT_DIR/empty.xml" collection=$DATA_DIR
else
    echo "No default transformation (default.xsl)" 1>&2
    echo "Try to run the image with '/bin/sh' as argument or read about extending in README.md" 1>&2
fi