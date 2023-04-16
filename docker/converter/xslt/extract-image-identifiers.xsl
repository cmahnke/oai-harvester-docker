<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:lido="http://www.lido-schema.org" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xs="http://www.w3.org/2001/XMLSchema" version="2.0"
    exclude-result-prefixes="xsi xs xsl lido">
    <xsl:param name="collection" select="''" as="xs:string"/>
    <xsl:output method="text"/>
    <xsl:variable name="break" select="'&#xa;'" as="xs:string"/>
    <xsl:variable name="prefix" select="'https://www.kenom.de/iiif/image/'" as="xs:string"/>
    <xsl:variable name="suffix" select="'/full/full/0/default.jpg'" as="xs:string"/>
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="$collection != ''">
                <xsl:for-each select="collection(concat($collection, '/?select=*.xml;recurse=no'))">
                    <xsl:apply-templates select="./lido:lido"/>
                </xsl:for-each>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>

    </xsl:template>
    <xsl:template match="//lido:linkResource[@lido:formatResource = 'image/jpeg']">
        <xsl:if test="ends-with(., $suffix)">
            <xsl:variable name="url" select="replace(replace(., $prefix, ''), $suffix, '')"/>
            <xsl:value-of select="$url"/>
            <xsl:value-of select="$break"/>
        </xsl:if>
    </xsl:template>
    <xsl:template match="text()" mode="#all"/>
</xsl:stylesheet>
