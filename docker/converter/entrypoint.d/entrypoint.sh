#!/bin/sh

echo "Starting Converter container"

if [ $# -ne 0 ] ; then
    echo "Running provided command '$@'"
    $@
    exit 0
fi

if [ -r $XSLT_DIR/default.xsl ] ; then
    java -Xmx1024m -cp $SAXON_DIR/saxon.jar:$SAXON_DIR/xmlresolver.jar net.sf.saxon.Transform -xsl:"$XSLT_DIR/default.xsl" -s:"$XSLT_DIR/empty.xml" collection=$DATA_DIR
else
    echo "No default transformation (default.xsl)"
    echo "Try to run the image with '/bin/sh' as argument or read about extending in README.md"
fi