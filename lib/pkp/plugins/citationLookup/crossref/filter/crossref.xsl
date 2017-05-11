<?xml version="1.0"?>
<!--
  * crossref.xsl
  *
  * Copyright (c) 2014-2017 Simon Fraser University
  * Copyright (c) 2000-2017 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Crosswalk from CrossRef XML to PKP citation elements
  -->

<xsl:transform version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	exclude-result-prefixes="xsl">

<xsl:output omit-xml-declaration="yes"/>

<xsl:strip-space elements="*"/>

<!--============================================
	START TRANSFORMATION AT THE ROOT NODE
==============================================-->
<xsl:template match="/">
	<element-citation>
		<xsl:apply-templates select="doi_records/doi_record/crossref/*"/>
	</element-citation>
</xsl:template>

<!--============================================
	JOURNAL METADATA
==============================================-->

<xsl:template match="journal">
	<!-- Publication Type -->
	<publication-type>journal</publication-type>

	<!-- Journal title -->
	<xsl:if test="journal_metadata/full_title">
		<source>
			<xsl:choose>
				<xsl:when test="journal_metadata/abbrev_title != ''">
					<xsl:value-of select="journal_metadata/abbrev_title"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="journal_metadata/full_title"/>
				</xsl:otherwise>
			</xsl:choose>
		</source>
	</xsl:if>

	<!-- ISSN -->
	<xsl:if test="journal_metadata/issn[@media_type='print']">
		<issn-ppub><xsl:value-of select="journal_metadata/issn"/></issn-ppub>
	</xsl:if>

	<!-- Issue -->
	<xsl:if test="journal_issue/issue">
		<issue><xsl:value-of select="journal_issue/issue"/></issue>
	</xsl:if>

	<!-- Volume -->
	<xsl:if test="journal_issue/journal_volume/volume">
		<volume><xsl:value-of select="journal_issue/journal_volume/volume"/></volume>
	</xsl:if>

	<!-- Article Title -->
	<xsl:if test="journal_article/titles/title">
		<article-title><xsl:value-of select="journal_article/titles/title"/></article-title>
	</xsl:if>

	<!-- Authors -->
	<xsl:apply-templates select="journal_article/contributors/person_name"/>

	<!-- Publication Date -->
	<xsl:apply-templates select="journal_article/publication_date"/>

	<!-- Pages -->
	<xsl:apply-templates select="journal_article/pages"/>

	<!-- Identifiers -->
	<xsl:apply-templates select="journal_article/doi_data"/>
</xsl:template>


<!--============================================
	BOOK METADATA
==============================================-->

<xsl:template match="book">
	<!-- Publication Type -->
	<publication-type>book</publication-type>

	<!-- Book Title -->
	<xsl:if test="book_metadata/titles/title">
		<source><xsl:value-of select="book_metadata/titles/title"/></source>
	</xsl:if>

	<!-- Authors -->
	<xsl:apply-templates select="book_metadata/contributors/person_name"/>

	<!-- Publication Date -->
	<xsl:apply-templates select="book_metadata/publication_date"/>

	<!-- Edition -->
	<xsl:if test="book_metadata/edition_number">
		<edition><xsl:value-of select="book_metadata/edition_number"/></edition>
	</xsl:if>

	<!-- ISBN -->
	<xsl:if test="book_metadata/isbn">
		<isbn><xsl:value-of select="book_metadata/isbn"/></isbn>
	</xsl:if>

	<!-- Publisher -->
	<xsl:apply-templates select="book_metadata/publisher"/>

	<!-- Identifiers -->
	<xsl:apply-templates select="book_metadata/doi_data"/>
</xsl:template>

<!--============================================
	CONFERENCE METADATA
==============================================-->

<xsl:template match="conference">
	<!-- Publication Type -->
	<publication-type>conf-proc</publication-type>

	<!-- Conference title -->
	<xsl:if test="proceedings_metadata/proceedings_title">
		<conf-name>
			<xsl:value-of select="proceedings_metadata/proceedings_title"/>
		</conf-name>
	</xsl:if>

	<!-- ISBN -->
	<xsl:if test="proceedings_metadata/isbn">
		<isbn><xsl:value-of select="proceedings_metadata/isbn"/></isbn>
	</xsl:if>

	<!-- Publisher -->
	<xsl:apply-templates select="proceedings_metadata/publisher"/>

	<!-- Article Title -->
	<xsl:if test="conference_paper/titles/title">
		<article-title><xsl:value-of select="conference_paper/titles/title"/></article-title>
	</xsl:if>

	<!-- Authors -->
	<xsl:apply-templates select="conference_paper/contributors/person_name"/>

	<!-- Publication Date -->
	<xsl:apply-templates select="conference_paper/publication_date"/>

	<!-- Pages -->
	<xsl:apply-templates select="conference_paper/pages"/>

	<!-- Identifiers -->
	<xsl:apply-templates select="conference_paper/doi_data"/>
</xsl:template>

<!--============================================
	COMMON ELEMENTS
==============================================-->

<!-- Authors:
		We have to concatenate surname and given name so that
		we can parse out initials later which are in the given name -->
<xsl:template match="person_name">
	<author>
		<xsl:value-of select="surname"/>, <xsl:value-of select="given_name"/>
	</author>
</xsl:template>

<!-- Publication Date -->
<xsl:template match="publication_date">
	<date>
		<xsl:if test="year"><xsl:value-of select="year"/><xsl:if test="month">-<xsl:value-of select="month"/><xsl:if test="day">-<xsl:value-of select="day"/></xsl:if></xsl:if></xsl:if>
	</date>
</xsl:template>

<!-- Pages -->
<xsl:template match="pages">
	<!-- First Page -->
	<xsl:if test="first_page">
		<fpage><xsl:value-of select="first_page"/></fpage>
	</xsl:if>

	<!-- Last Page -->
	<xsl:if test="last_page">
		<lpage><xsl:value-of select="last_page"/></lpage>
	</xsl:if>
</xsl:template>

<!-- Identifiers -->
<xsl:template match="doi_data">
	<!-- DOI -->
	<xsl:if test="doi">
		<pub-id-doi><xsl:value-of select="doi"/></pub-id-doi>
	</xsl:if>

	<!-- URI; NB: may not be Open Access / full text -->
	<xsl:if test="resource">
		<uri><xsl:value-of select="resource"/></uri>
	</xsl:if>

	<!--
	TODO: More identifier info: SICI, ...
	 -->
</xsl:template>

<!-- Publisher -->
<xsl:template match="publisher">
	<xsl:if test="publisher_name">
		<publisher-name><xsl:value-of select="publisher_name"/></publisher-name>
	</xsl:if>

	<!-- Publisher Location -->
	<xsl:if test="publisher_place">
		<publisher-loc><xsl:value-of select="publisher_place"/></publisher-loc>
	</xsl:if>
</xsl:template>

</xsl:transform>
