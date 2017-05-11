<?xml version="1.0"?>
<!--
  * isbndb.xsl
  *
  * Copyright (c) 2014-2017 Simon Fraser University
  * Copyright (c) 2000-2017 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Crosswalk from ISBNdb API XML to PKP citation elements
  -->

<xsl:transform version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	exclude-result-prefixes="xsl">

<xsl:output omit-xml-declaration='yes'/>

<xsl:strip-space elements="*"/>

<!--============================================
	START TRANSFORMATION AT THE ROOT NODE
==============================================-->
<xsl:template match="/">
	<element-citation>
		<xsl:apply-templates select="ISBNdb/BookList/BookData/*"/>
	</element-citation>
</xsl:template>

<!-- Book title -->
<xsl:template match="TitleLong">
	<source>
		<xsl:choose>
			<xsl:when test=". != ''">
				<xsl:value-of select="."/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="../Title"/>
			</xsl:otherwise>
		</xsl:choose>
	</source>
</xsl:template>

<!-- Authors -->
<xsl:template match="Authors">
	<xsl:for-each select="Person">
		<author><xsl:value-of select="."/></author>
	</xsl:for-each>
</xsl:template>

<!-- Publisher & Location -->
<xsl:template match="PublisherText">
	<place-publisher><xsl:value-of select="."/></place-publisher>

	<!-- also possible year in Details/@edition_info -->
	<date><xsl:value-of select="."/></date>
</xsl:template>

<!-- possible edition in Details/@edition_info -->

<!-- Ignore everything else -->
<xsl:template match="*"/>

</xsl:transform>
