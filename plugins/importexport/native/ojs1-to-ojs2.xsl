<?xml version="1.0"?>

<!--
  * ojs1-to-ojs2.xsl
  *
  * Copyright (c) 2003-2008 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * This XSL stylesheet transforms OJS 1.x import XML into OJS 2.x import XML.
  * It is currently functional but incomplete and unlikely to receive much
  * attention; please feel free to contribute additions and updates by
  * contacting the OJS team at pkp-support@sfu.ca.
  *
  * $Id$
  -->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/TR/xhtml1/strict">
	<!-- ............................... -->
	<!-- .. ARCHIVE-CONTEXT TEMPLATES .. -->
	<!-- ............................... -->

	<xsl:template match="archive">
		<issues>
			<xsl:apply-templates select="issue" />
		</issues>
	</xsl:template>

	<xsl:template match="issue">
		<issue>
			<xsl:apply-templates select="issue_description|section" />
		</issue>
	</xsl:template>

	<xsl:template match="issue_description/title">
		<title><xsl:value-of select="." /></title>
	</xsl:template>

	<xsl:template match="issue_description/volume">
		<volume><xsl:value-of select="." /></volume>
	</xsl:template>

	<xsl:template match="issue_description/number">
		<number><xsl:value-of select="." /></number>
	</xsl:template>

	<xsl:template match="issue_description/year">
		<year><xsl:value-of select="." /></year>
	</xsl:template>

	<xsl:template match="section_name">
		<section>
			<title><xsl:value-of select="." /></title>
			<xsl:variable name="sectionTitle" select="text()"/>
			<xsl:for-each select="../../article">
				<xsl:if test="$sectionTitle=./section">
					<xsl:apply-templates select="."/>
				</xsl:if>
			</xsl:for-each>
		</section>
	</xsl:template>

	<xsl:template match="section_order"><!-- Ignored --></xsl:template>
	<xsl:template match="section_order"><!-- Ignored --></xsl:template>

	<!-- ............................... -->
	<!-- .. ARTICLE-CONTEXT TEMPLATES .. -->
	<!-- ............................... -->
	<xsl:template match="article">
		<article>
			<xsl:attribute name="language">
				<xsl:apply-templates select="language"/>
			</xsl:attribute>
			<xsl:apply-templates select="title"/>
			<xsl:apply-templates select="abstract"/>
			<xsl:apply-templates select="date_published"/>
			<xsl:apply-templates select="author"/>
			<indexing>
				<xsl:apply-templates select="discipline"/>
				<xsl:apply-templates select="subject_class"/>
				<xsl:apply-templates select="subject"/>
				<coverage>
					<xsl:apply-templates select="coverage_geo"/>
					<xsl:apply-templates select="coverage_chron"/>
					<xsl:apply-templates select="coverage_sample"/>
				</coverage>
			</indexing>
			<xsl:apply-templates select="html"/>
			<xsl:apply-templates select="pdf"/>
		</article>
	</xsl:template>

	<xsl:template match="article/title">
		<title><xsl:value-of select="."/></title>
	</xsl:template>

	<xsl:template match="article/abstract">
		<abstract><xsl:value-of select="."/></abstract>
	</xsl:template>

	<xsl:template match="article/number"><!-- Ignored --></xsl:template>

	<xsl:template match="article/date_published">
		<date_published><xsl:value-of select="."/></date_published>
	</xsl:template>

	<xsl:template match="pdf">
		<galley>
			<label>PDF</label>
			<file>
				<href mime_type="application/pdf">
					<xsl:attribute name="src">
						<xsl:value-of select="."/>
					</xsl:attribute>
				</href>
			</file>
		</galley>
	</xsl:template>

	<xsl:template match="html">
		<htmlgalley>
			<label>HTML</label>
			<file>
				<href mime_type="text/html">
					<xsl:attribute name="src">
						<xsl:value-of select="."/>
					</xsl:attribute>
				</href>
			</file>
		</htmlgalley>
	</xsl:template>

	<xsl:template match="discipline">
		<discipline><xsl:value-of select="."/></discipline>
	</xsl:template>

	<xsl:template match="subject_class">
		<subject_class><xsl:value-of select="."/></subject_class>
	</xsl:template>

	<xsl:template match="subject">
		<subject><xsl:value-of select="."/></subject>
	</xsl:template>

	<xsl:template match="coverage_geo">
		<geographical><xsl:value-of select="."/></geographical>
	</xsl:template>

	<xsl:template match="coverage_chron">
		<chronological><xsl:value-of select="."/></chronological>
	</xsl:template>

	<xsl:template match="coverage_sample">
		<sample><xsl:value-of select="."/></sample>
	</xsl:template>

	<xsl:template match="language"><xsl:value-of select="."/></xsl:template>

	<!-- .............................. -->
	<!-- .. AUTHOR-CONTEXT TEMPLATES .. -->
	<!-- .............................. -->
	<xsl:template match="author">
		<author>
			<xsl:attribute name="primary_contact">
				<xsl:apply-templates select="author_primary"/>
			</xsl:attribute>
			<xsl:apply-templates select="first_name"/>
			<xsl:apply-templates select="middle_initial"/>
			<xsl:apply-templates select="last_name"/>
			<xsl:apply-templates select="affiliation"/>
			<xsl:apply-templates select="email"/>
			<xsl:apply-templates select="biography"/>
			<xsl:apply-templates select="author_order"/>
		</author>
	</xsl:template>

	<xsl:template match="first_name">
		<firstname><xsl:value-of select="."/></firstname>
	</xsl:template>

	<xsl:template match="middle_initial">
		<middlename><xsl:value-of select="."/></middlename>
	</xsl:template>

	<xsl:template match="last_name">
		<lastname><xsl:value-of select="."/></lastname>
	</xsl:template>

	<xsl:template match="affiliation">
		<affiliation><xsl:value-of select="."/></affiliation>
	</xsl:template>

	<xsl:template match="email">
		<email><xsl:value-of select="."/></email>
	</xsl:template>

	<xsl:template match="biography">
		<biography><xsl:value-of select="."/></biography>
	</xsl:template>

	<xsl:template match="last_name">
		<lastname><xsl:value-of select="."/></lastname>
	</xsl:template>

	<xsl:template match="author_order"><!-- Ignored --></xsl:template>
	<xsl:template match="author_primary">
		<xsl:choose>
			<xsl:when test="text()='1'">true</xsl:when>
			<xsl:otherwise>false</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
