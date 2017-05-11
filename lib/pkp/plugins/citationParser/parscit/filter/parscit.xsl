<?xml version='1.0' encoding='utf-8'?>
<!--
  * parscit.xsl
  *
  * Copyright (c) 2014-2017 Simon Fraser University
  * Copyright (c) 2000-2017 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Simple mapping from Parscit web service into
  * a flat XML for conversion into a PHP array
  -->

<xsl:transform version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		exclude-result-prefixes="xsl">

<xsl:strip-space elements="*"/>

<xsl:template match="/citationList">
	<element-citation>
		<xsl:apply-templates select="citation/*"/>
	</element-citation>
</xsl:template>

<!-- Genre -->
<xsl:template match="*[local-name() = 'genre']">
	<publication-type>
		<xsl:choose>
			<!-- map to interal options -->
			<xsl:when test=". = 'article'">journal</xsl:when>
			<xsl:when test=". = 'proceeding'">conf-proc</xsl:when>
			<xsl:otherwise>unknown</xsl:otherwise>
		</xsl:choose>
	</publication-type>
</xsl:template>

<!-- Authors -->
<xsl:template match="authors">
	<xsl:copy-of select="*"/>
</xsl:template>

<!-- Article title -->
<xsl:template match="title">
	<article-title><xsl:value-of select="."/></article-title>
</xsl:template>

<!-- Book title -->
<xsl:template match="booktitle">
	<source><xsl:value-of select="."/></source>
</xsl:template>

<!-- Journal title -->
<xsl:template match="journal">
	<source><xsl:value-of select="."/></source>
</xsl:template>

<!-- Date -->
<xsl:template match="date">
	<date><xsl:value-of select="."/></date>
</xsl:template>

<!-- Publisher -->
<xsl:template match="publisher">
	<publisher-name><xsl:value-of select="."/></publisher-name>
</xsl:template>

<!-- Location -->
<xsl:template match="location">
	<publisher-loc><xsl:value-of select="."/></publisher-loc>
</xsl:template>

<!-- Pages -->
<xsl:template match="*[local-name() = 'pages']">
	<fpage><xsl:value-of select="substring-before(., '--')"/></fpage>
	<lpage><xsl:value-of select="substring-after(., '--')"/></lpage>
</xsl:template>

<!-- Comments -->
<xsl:template match="note | notes | tech">
	<comment><xsl:value-of select="."/></comment>
</xsl:template>

<!-- copy element and value -->
<xsl:template match="*">
	<xsl:element name="{local-name()}"><xsl:value-of select="."/></xsl:element>
</xsl:template>

</xsl:transform>
