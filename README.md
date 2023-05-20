Simple OAI Harvester as Docker image
====================================

These Docker images can be used to either download and convert metadata files or to build prepopulated images containing data. they can be used to provide Docker volumes for enrichment or Kubernetes side cars to populate persistent volumes.

# Building 

## Harvester

```
docker buildx build -f docker/harvester/Dockerfile .
```

## Converter

```
docker buildx build -f docker/converter/Dockerfile .
```

# Running

## Harvester

### With configuration file

Just edit the file `harvester/config.yaml` and mount it readonly for the run. Also provide a data directory:

```
docker run -it --mount type=bind,source="$(pwd)"/data,target=/data --mount type=bind,source="$(pwd)"/harvester/config.yaml,target=/opt/harvester/config.yaml,readonly ghcr.io/cmahnke/oai-harvester-docker/harvester
```

Makes sure to change the `/data` mount target to the path in your `config.yaml` and that the local directory exits.

### With enviroment vars

Pass configuration via environment, also make sure too provide a data directory:

```
docker run -it -e OAI_URL=https://www.kenom.de/oai/ -e METADATA_PREFIX=lido -e SET=institution:DE-MUS-062622 -e TARGET_DIR=/data --mount type=bind,source="$(pwd)"/data,target=/data ghcr.io/cmahnke/oai-harvester-docker/harvester
```

See table below for possible values.

## Converter

### Authentificated OAI

```
docker run -e OAI_USER=$OAI_USER -e OAI_PASS=$OAI_PASS --mount type=bind,source="$(pwd)"/harvester/config.yaml,target=/opt/harvester/config.yaml,readonly --mount type=bind,source="$(pwd)"/data,target=/data ghcr.io/cmahnke/oai-harvester-docker/converter
```

### Using a default transformation

If a file `/opt/xslt/dafault.xsl` is provided, the container will use it to transform data.

```
docker run -it --mount type=bind,source="$(pwd)"/conf/config.yaml,target=/opt/harvester/config.yaml,readonly --mount type=bind,source="$(pwd)"/data,target=/data --mount type=bind,source="$(pwd)"/xslt/stylesheet.xsl,target=/opt/xslt/default.xsl ghcr.io/cmahnke/oai-harvester-docker/converter
```

## Configuration file

| Name            | Value                                                                                        |
|-----------------|----------------------------------------------------------------------------------------------|
| oai_url         | URL of the OAI PMH endpoint of a repository                                                  |
| metadata_prefix | The matadata prefix (format) to use                                                          |
| set             | The requested metadata set                                                                   |
| target_dir      | The direcory inside the container to save files to, can be mounted                           |
| user            | Username for authentification                                                                |
| pass            | Password for authentification                                                                |
| mode            | Change to 'ListIdentifiers' if you want to get each Record by listing the identifiers first  |

These configuration settings can also be passed via env in upper case.

# Building prepopulated volumes

The esiest way to use the images to provide prepopulated volumes is to build your own Docker images and referencing the base images in `FROM`.

The `converter` image provides `saxon`, `jq` and `curl` for transformations and downloading. These can also be used during the build of an image. The script `/usr/local/sbin/cleanup.sh` removes these tools to keep the image small.

A possible Workflow would be:
* Extend the `converter` image
* Provide a `config.yaml` for the desired OAI interface
* Start the harvester
* Convert all metadata files
* Clean the image from tools using `cleanup.sh`

## Variables

The following variables are provided

| Name           | Value          |
|----------------|----------------|
| HARVESTER_HOME | /opt/harvester |
| SAXON_DIR      | /opt/saxon     |
| XSLT_DIR       | /opt/xslt      |
| DATA_DIR       | /data          |

## Running Saxon

Running Saxon from within the `Dockerfile` is quite easy, make sure you pass the class path (`-cp`) right, including `$SAXON_DIR/saxon.jar` and `$SAXON_DIR/xmlresolver.jar`. The following example uses an empty input XML file (`empty.xml`), since the transformation works directly on a collection (`DATA_DIR`). 

```
java -Xmx1024m -cp $SAXON_DIR/saxon.jar:$SAXON_DIR/xmlresolver.jar net.sf.saxon.Transform -xsl:"/opt/xslt/extract-image-identifiers.xsl" -s:"/opt/xslt/empty.xml" collection=$DATA_DIR > /data/identifiers.lst && \
```

## Downloading files
You can also use `curl` to download files that might have been extracted from the metadata into a file list:
```
URLPREFIX=https://imageurl
while read IDENTIFIER; do
    echo "Requesting $URLPREFIX$IDENTIFIER"
    curl -sS -o /dev/null "$URLPREFIX$IDENTIFIER"
done < /data/identifiers.lst
```
