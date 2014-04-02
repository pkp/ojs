<?xml version="1.0"?>

<!--
  * plugins/generic/xmlGalley/transform/test.xsl
  *
  * Copyright (c) 2013-2014 Simon Fraser University Library
  * Copyright (c) 2003-2014 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Test XSL stylesheet for external XSLT using the XML Galleys plugin.
  *
  -->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="text" omit-xml-declaration="yes"/>
<xsl:strip-space elements="*"/>

	<xsl:template match="/root">
		<xsl:apply-templates/>
	</xsl:template>
	
	<xsl:template match="level_1">
		<xsl:value-of select="level_2"/>
		<xsl:apply-templates/>

		<xsl:variable name="test"> Success</xsl:variable>
		<xsl:value-of select="$test"/>
	</xsl:template>

	<xsl:template match="level_2"/>
</xsl:stylesheet>
