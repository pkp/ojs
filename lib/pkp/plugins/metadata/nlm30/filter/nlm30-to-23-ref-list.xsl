<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<!-- Elements to be copied unchanged (shallow copy): -->
	<xsl:template match="ref-list|ref|label|article-title|source|year|month|day|issue|volume|season|edition|series|supplement|conf-name|conf-date|conf-loc|conf-sponsor|institution|fpage|lpage|publisher-loc|publisher-name|isbn|issn|pub-id|uri|comment|annotation">
		<xsl:copy>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xsl:copy>
	</xsl:template>

	<!-- Elements to be copied unchanged (deep copy): -->
	<xsl:template match="person-group">
		<xsl:copy-of select="."/>
	</xsl:template>

	<!-- Elements to be changed -->
	<xsl:template match="element-citation">
		<xsl:element name="citation">
			<xsl:attribute name="citation-type">
				<xsl:choose>
					<xsl:when test="@publication-type='conf-proc'">
						<xsl:text>confproc</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="@publication-type"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="date-in-citation[@content-type='access-date']">
		<xsl:element name="access-date">
			<xsl:value-of select="year"/>
			<xsl:if test="month!=''">
				<xsl:text>-</xsl:text>
				<xsl:if test="string-length(month)=1">
					<xsl:text>0</xsl:text>
				</xsl:if>
				<xsl:value-of select="month"/>
				<xsl:if test="day!=''">
					<xsl:text>-</xsl:text>
					<xsl:if test="string-length(day)=1">
						<xsl:text>0</xsl:text>
					</xsl:if>
					<xsl:value-of select="day"/>
				</xsl:if>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="chapter-title">
		<xsl:element name="article-title">
			<xsl:value-of select="."/>
		</xsl:element>
	</xsl:template>

	<!-- Elements to be discarded -->
	<xsl:template match="size"/>
</xsl:stylesheet>