<?xml version="1.0" encoding="UTF-8"?>
<!--
  * @file plugins/oaiMetadataFormats/jats/transform.xsl
  *
  * Copyright (c) 2013-2017 Simon Fraser University
  * Copyright (c) 2003-2017 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Apply transformations to JATS XML before serving them via OAI-PMH
  -->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<!--
	  - Parameters received from PHP-land
	  -->
	<xsl:param name="datePublished"/><!-- The publication date, formatted ISO8601 -->
	<xsl:param name="datePublishedDay"/><!-- The day part of the publication date -->
	<xsl:param name="datePublishedMonth"/><!-- The month part of the publication date -->
	<xsl:param name="datePublishedYear"/><!-- The year part of the publication date -->
	<xsl:param name="title"/><!-- The title (for the submission's primary locale) -->
	<xsl:param name="abstract"/><!-- The abstract (for the submission's primary locale) in stripped-down HTML -->
	<xsl:param name="copyrightHolder"/><!-- The copyright holder (for the submission's primary locale) -->
	<xsl:param name="copyrightYear"/><!-- The copyright year -->
	<xsl:param name="licenseUrl"/><!-- The license URL -->
	<xsl:param name="isUnpublishedXml"/><!-- Whether or not this XML document is published (e.g. via Lens Reader); 1 or 0 -->

	<!--
	  - Identity transform
	  -->
	<!-- This permits almost all content to pass through unmodified. -->
	<xsl:template match="@*|node()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()"/>
		</xsl:copy>
	</xsl:template>

	<xsl:template match="article-meta">
		<xsl:call-template name="doi-check"/>
		<xsl:call-template name="abstract-check"/>
		<xsl:call-template name="permissions-check"/>
		<xsl:apply-templates/>
	</xsl:template>

	<!--
	  - Permissions
	  -->
	<!-- When no permissions information exists in the document, stamp it from OJS -->
	<xsl:template name="permissions-check">
		<xsl:if test="not(permissions)">
			<xsl:if test="$copyrightYear != '' or $copyrightHolder != '' or $licenseUrl != ''">
				<permissions>
					<xsl:if test="$copyrightYear != ''"><copyright-year><xsl:value-of select="$copyrightYear"/></copyright-year></xsl:if>
					<xsl:if test="$copyrightHolder != ''"><copyright-holder><xsl:value-of select="$copyrightHolder"/></copyright-holder></xsl:if>
					<xsl:if test="$licenseUrl != ''">
						<license>
							<xsl:attribute namespace="xlink" name="href"><xsl:value-of select="$licenseUrl"/></xsl:attribute>
						</license>
					</xsl:if>
				</permissions>
			</xsl:if>
		</xsl:if>
	</xsl:template>

	<!--
	  - DOI
	  -->
	<!-- For when no DOI exists in the document -->
	<xsl:template name="doi-check">
		<xsl:if test="not(article-id[@pub-id-type='doi'])">
			<xsl:if test="$doi != ''">
				<article-id pub-id-type="doi"><xsl:value-of select="$doi"/></article-id>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	<!-- For when a DOI exists, replace it -->
	<xsl:template match="article-id[@pub-id-type='doi']">
		<xsl:choose>
			<xsl:when test="$doi != ''">
				<article-id pub-id-type="doi"><xsl:value-of select="$doi"/></article-id>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!--
	  - Publication date
	  -->
	<!-- This element is presumed to exist in the source document. -->
	<xsl:template match="pub-date[@date-type='pub']">
		<pub-date date-type="pub" publication-format="print" iso-8601-date="2002-04-13">
			<xsl:attribute name="iso-8601-date">
				<xsl:value-of select="$datePublished"/>
			</xsl:attribute>
			<day><xsl:value-of select="$datePublishedDay"/></day>
			<month><xsl:value-of select="$datePublishedMonth"/></month>
			<year><xsl:value-of select="$datePublishedYear"/></year>
		</pub-date>
	</xsl:template>

	<!--
	  - Article title
	  -->
	<!-- This element is presumed to exist in the source document. -->
	<xsl:template match="title-group">
		<title-group>
			<article-title><xsl:value-of select="$title"/></article-title>
		</title-group>
	</xsl:template>

	<!--
	  - Article abstract
	  -->
	<!-- Add an abstract element when none exists --><!-- match="article-meta[not(abstract)]" -->
	<xsl:template name="abstract-check">
		<xsl:if test="not(abstract)">
			<abstract><xsl:value-of select="$abstract" disable-output-escaping="yes"/></abstract>
		</xsl:if>
	</xsl:template>
	<!-- Update an element when one does exist -->
	<xsl:template match="abstract">
		<abstract>
			<xsl:value-of select="$abstract" disable-output-escaping="yes"/>
		</abstract>
	</xsl:template>
</xsl:stylesheet>
