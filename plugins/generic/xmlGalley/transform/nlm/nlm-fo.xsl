<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        xmlns:fo="http://www.w3.org/1999/XSL/Format"
        xmlns:exsl="http://exslt.org/common"
        xmlns:rx="http://www.renderx.com/XSL/Extensions"
        xmlns:fox="http://xml.apache.org/fop/extensions"
        xmlns:mml="http://www.w3.org/1998/Math/MathML"
        xmlns:svg="http://www.w3.org/2000/svg"
        exclude-result-prefixes="exsl rx fox mml svg"
        version="1.0">

    <xsl:output method="xml" indent="yes" encoding="utf-8"/>

    <xsl:strip-space elements="*"/>

    <xsl:param name="xep.extensions" select="0"/>
    <xsl:param name="fop.extensions" select="0"/>

    <xsl:param name="image_dir"/>
    <!--<xsl:param name="start_page"/>-->

    <xsl:param name="body.font.family" select="'serif'"/>
    <xsl:param name="monospace.font.family" select="'monospace'"/>
    <xsl:param name="symbol.font.family" select="'Symbol,ZapfDingbats'"/>
    <xsl:param name="title.font.family" select="'sans-serif'"/>
    <xsl:param name="heading.font.family" select="'Helvetica,Symbol,ZapfDingbats'"/>

    <xsl:param name="title.margin.left" select="'0pt'"/>
    <xsl:param name="body.margin.bottom" select="'0.5in'"/>
    <xsl:param name="body.margin.top" select="'0.5in'"/>
    <xsl:param name="column.count.body" select="2"/>
    <xsl:param name="column.gap.body" select="'12pt'"/>
    <xsl:param name="page.margin.bottom" select="'0.5in'"/>
    <xsl:param name="page.margin.inner" select="'1in'"/>
    <xsl:param name="page.margin.outer" select="'1in'"/>
    <xsl:param name="page.margin.top" select="'0.5in'"/>
    <xsl:param name="page.orientation" select="'portrait'"/>
    <xsl:param name="paper.type" select="'USletter'"/>
    <xsl:param name="region.after.extent" select="'0.4in'"/>
    <xsl:param name="region.before.extent" select="'0.4in'"/>
    <xsl:param name="hyphenate" select="'true'"/>

    <xsl:param name="page.height">
        <xsl:choose>
            <xsl:when test="$paper.type = 'USletter'">11in</xsl:when>
            <xsl:when test="$paper.type = 'A4'">297mm</xsl:when>
            <xsl:otherwise>297mm</xsl:otherwise>
        </xsl:choose>
    </xsl:param>

    <xsl:param name="page.width">
        <xsl:choose>
            <xsl:when test="$paper.type = 'USletter'">8.5in</xsl:when>
            <xsl:when test="$paper.type = 'A4'">210mm</xsl:when>
            <xsl:otherwise>210mm</xsl:otherwise>
        </xsl:choose>
    </xsl:param>

    <xsl:template match="*">
        <!--<xsl:message>
            <xsl:value-of select="name(.)"/>
            <xsl:text> encountered</xsl:text>
            <xsl:if test="parent::*">
                <xsl:text> in </xsl:text>
                <xsl:value-of select="name(parent::*)"/>
            </xsl:if>
            <xsl:text>, but no template matches.</xsl:text>
        </xsl:message>
        <fo:block color="red">
            <xsl:text>&lt;</xsl:text>
            <xsl:value-of select="name(.)"/>
            <xsl:text>&gt;</xsl:text>
            <xsl:apply-templates/>
            <xsl:text>&lt;/</xsl:text>
            <xsl:value-of select="name(.)"/>
            <xsl:text>&gt;</xsl:text>
        </fo:block>--></xsl:template>

    <xsl:variable name="body.font.size" select="'10pt'"/>
    <xsl:variable name="small.font.size" select="'9pt'"/>
    <xsl:variable name="heading.font.size" select="'11pt'"/>
    <xsl:variable name="title.font.size" select="'14pt'"/>
    <xsl:variable name="meta.font.size" select="'7pt'"/>

    <xsl:key name="subjects" match="Article" use="PublicationType"/>
    <xsl:key name="subjects2" match="article" use="front/article-meta/article-categories/subj-group/subject"/>

    <xsl:template match="/">
        <fo:root font-size="{$body.font.size}" font-family="{$body.font.family},{$symbol.font.family}" text-align="justify" line-height="normal" font-selection-strategy="character-by-character" line-height-shift-adjustment="disregard-shifts">

            <xsl:choose>
                <xsl:when test="$xep.extensions = 1">
                    <xsl:call-template name="xep-document-information"/>
                </xsl:when>
                <xsl:when test="$fop.extensions = 1">
                    <xsl:call-template name="fop-document-information"/>
                </xsl:when>
            </xsl:choose>

            <fo:layout-master-set>

                <fo:simple-page-master master-name="body-odd" page-width="{$page.width}" page-height="{$page.height}" margin-top="{$page.margin.top}" margin-bottom="{$page.margin.bottom}" margin-left="{$page.margin.inner}" margin-right="{$page.margin.outer}">
                    <fo:region-body region-name="body-std" margin-bottom="{$body.margin.bottom}" margin-top="{$body.margin.top}" column-gap="{$column.gap.body}" column-count="{$column.count.body}"/>
                    <fo:region-before extent="{$region.before.extent}" display-align="before"/>
                    <fo:region-after extent="{$region.after.extent}" display-align="after"/>
                </fo:simple-page-master>

                <fo:simple-page-master master-name="body-even" page-width="{$page.width}" page-height="{$page.height}" margin-top="{$page.margin.top}" margin-bottom="{$page.margin.bottom}" margin-left="{$page.margin.inner}" margin-right="{$page.margin.outer}">
                    <fo:region-body region-name="body-std" margin-bottom="{$body.margin.bottom}" margin-top="{$body.margin.top}" column-gap="{$column.gap.body}" column-count="{$column.count.body}"/>
                    <fo:region-before extent="{$region.before.extent}" display-align="before"/>
                    <fo:region-after extent="{$region.after.extent}" display-align="after"/>
                </fo:simple-page-master>

                <!--<fo:simple-page-master master-name="title-odd" page-width="{$page.width}" page-height="{$page.height}" margin-top="{$page.margin.top}" margin-bottom="{$page.margin.bottom}" margin-left="{$page.margin.inner}" margin-right="{$page.margin.outer}">
                    <fo:region-body region-name="body" margin-bottom="{$body.margin.bottom}" margin-top="{$body.margin.top}" column-gap="{$column.gap.body}" column-count="{$column.count.body}"/>
                    <fo:region-before region-name="title" extent="{$region.before.extent}" display-align="before"/>
                    <fo:region-after region-name="footer" extent="{$region.after.extent}" display-align="after"/>
                </fo:simple-page-master>

                <fo:simple-page-master master-name="title-even" page-width="{$page.width}" page-height="{$page.height}" margin-top="{$page.margin.top}" margin-bottom="{$page.margin.bottom}" margin-left="{$page.margin.inner}" margin-right="{$page.margin.outer}">
                    <fo:region-body region-name="body" margin-bottom="{$body.margin.bottom}" margin-top="{$body.margin.top}" column-gap="{$column.gap.body}" column-count="{$column.count.body}"/>
                    <fo:region-before region-name="title" extent="{$region.before.extent}" display-align="before"/>
                    <fo:region-after region-name="footer" extent="{$region.after.extent}" display-align="after"/>
                </fo:simple-page-master>-->

                <!--<fo:page-sequence-master master-name="titlepage">
                    <fo:repeatable-page-master-alternatives>
                        <fo:conditional-page-master-reference master-reference="title-odd" odd-or-even="odd"/>
                        <fo:conditional-page-master-reference master-reference="title-even" odd-or-even="even"/>
                    </fo:repeatable-page-master-alternatives>
                </fo:page-sequence-master>-->

                <fo:page-sequence-master master-name="contents">
                    <fo:repeatable-page-master-alternatives>
                        <fo:conditional-page-master-reference master-reference="body-odd" odd-or-even="odd"/>
                        <fo:conditional-page-master-reference master-reference="body-even" odd-or-even="even"/>
                    </fo:repeatable-page-master-alternatives>
                </fo:page-sequence-master>

            </fo:layout-master-set>

            <xsl:choose>
                <xsl:when test="$xep.extensions = 1">
                    <xsl:variable name="bookmarks">
                        <xsl:apply-templates mode="xep.outline"/>
                    </xsl:variable>
                    <xsl:if test="string($bookmarks) != ''">
                        <rx:outline>
                            <xsl:copy-of select="$bookmarks"/>
                        </rx:outline>
                    </xsl:if>
                </xsl:when>
                <xsl:when test="$fop.extensions = 1">
                    <xsl:variable name="bookmarks">
                        <xsl:apply-templates mode="fop.outline"/>
                    </xsl:variable>
                </xsl:when>
            </xsl:choose>

            <xsl:apply-templates/>

        </fo:root>
    </xsl:template>

    <xsl:template match="article">
        <xsl:variable name="start-page">
            <xsl:choose>
                <xsl:when test="front/article-meta/fpage">
                    <xsl:value-of select="front/article-meta/fpage"/>
                </xsl:when>
                <!--<xsl:when test="front/article-meta/elocation-id">
                    <xsl:value-of select="substring-after(front/article-meta/elocation-id, 'e')"/>
                </xsl:when>-->
                <xsl:otherwise>
                    <xsl:text>1</xsl:text>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <fo:page-sequence master-reference="contents" initial-page-number="{$start-page}">

            <fo:static-content flow-name="xsl-region-before"><!-- header -->
                <fo:block>
                    <fo:table table-layout="auto" width="100%">
                        <fo:table-column/>
                        <fo:table-column/>
                        <fo:table-body>
                            <fo:table-row>
                                <fo:table-cell>
                                    <fo:block font-size="{$meta.font.size}">
                                        <xsl:value-of select="front/journal-meta/journal-title"/>
                                    </fo:block>
                                </fo:table-cell>
                                <fo:table-cell>
                                    <fo:block text-align="end" font-size="{$meta.font.size}">
                                        <xsl:variable name="authors" select="front/article-meta/contrib-group/contrib[@contrib-type='author']"/>
                                        <xsl:choose>
                                            <xsl:when test="count($authors) = 1">
                                                <xsl:value-of select="$authors[1]/name/surname"/>
                                            </xsl:when>
                                            <xsl:when test="count($authors) = 2">
                                                <xsl:value-of select="$authors[1]/name/surname"/> &amp; <xsl:value-of select="$authors[2]/name/surname"/>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:value-of select="$authors[1]/name/surname"/> et al</xsl:otherwise>
                                        </xsl:choose>
                                    </fo:block>
                                </fo:table-cell>
                            </fo:table-row>
                        </fo:table-body>
                    </fo:table>
                </fo:block>
            </fo:static-content>

            <fo:static-content flow-name="xsl-region-after"><!-- footer -->
                <fo:table table-layout="auto" width="100%">
                    <fo:table-column/>
                    <fo:table-column/>
                    <fo:table-body>
                        <fo:table-row>
                            <fo:table-cell>
                                <fo:block font-size="{$meta.font.size}">
                                    <fo:basic-link external-destination="url({front/article-meta/self-uri/@xlink:href})">
                                        <xsl:value-of select="front/article-meta/self-uri/@xlink:href"/>
                                    </fo:basic-link>
                                </fo:block>
                            </fo:table-cell>
                            <fo:table-cell>
                                <fo:block font-size="{$meta.font.size}" text-align="right">
                                    <fo:page-number/>
                                </fo:block>
                            </fo:table-cell>
                        </fo:table-row>
                    </fo:table-body>
                </fo:table>
            </fo:static-content>

            <fo:flow flow-name="body-std"><!-- body -->
                <xsl:apply-templates select="front"/>
                <xsl:apply-templates select="body"/>
                <xsl:apply-templates select="back"/>
                <xsl:apply-templates select="front/article-meta/pub-date[@pub-type='epub']"/>
                <xsl:apply-templates select="front/article-meta/contrib-group" mode="back"/>
                <xsl:apply-templates select="front/article-meta/copyright-statement"/>
            </fo:flow>

        </fo:page-sequence>
    </xsl:template>

    <xsl:template match="front">
        <!--<fo:block font-family="{$heading.font.family}" font-size="{$heading.font.size}" span="all">
            <xsl:value-of select="article-meta/article-categories/subj-group[@subj-group-type='article-type']/subject"/>
        </fo:block>-->

        <fo:block font-family="{$heading.font.family}" font-size="{$title.font.size}" span="all" id="{article-meta/article-id[@pub-id-type='publisher-id']}">
            <xsl:value-of select="article-meta/title-group/article-title"/>
        </fo:block>

        <xsl:apply-templates select="article-meta/contrib-group"/>

        <xsl:apply-templates select="article-meta/abstract"/>

        <fo:block font-size="{$body.font.size}" space-after.optimum="0.25in" span="all">
            <fo:block>
                <xsl:value-of select="journal-meta/journal-id[@journal-id-type='nlm-ta']"/>
                <xsl:text>. </xsl:text>
                <xsl:value-of select="article-meta/pub-date[@pub-type='epub']/year"/>
                <xsl:text>; </xsl:text>
                <xsl:value-of select="article-meta/volume"/>
                <xsl:text>(</xsl:text>
                <xsl:value-of select="article-meta/issue"/>
                <xsl:text>):</xsl:text>
                <xsl:value-of select="article-meta/elocation-id"/>
            </fo:block>
            <xsl:if test="article-meta/article-id[@pub-id-type='doi']">
                <fo:block>
                    <xsl:text>DOI: </xsl:text>
                    <fo:basic-link external-destination="url(http://dx.doi.org/{article-meta/article-id[@pub-id-type='doi']})" color="blue">
                        <xsl:value-of select="article-meta/article-id[@pub-id-type='doi']"/>
                    </fo:basic-link>
                </fo:block>
            </xsl:if>
        </fo:block>

    </xsl:template>

    <xsl:template match="body">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="back">
        <xsl:apply-templates select="ack"/>
        <xsl:apply-templates select="notes"/>
        <xsl:apply-templates select="fn-group"/>
        <xsl:apply-templates select="app-group"/>
        <xsl:apply-templates select="ref-list"/>
        <xsl:apply-templates select="glossary"/>
    </xsl:template>

    <xsl:template match="article-meta/abstract">
        <fo:block space-before.optimum="0.25in" space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" span="all">Abstract</fo:block>
        <fo:block span="all" space-after.optimum="0.25in">
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="glossary">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="contrib-group">
        <fo:block space-before.optimum="0.25in" font-size="{$heading.font.size}" span="all">
            <xsl:apply-templates select="contrib[@contrib-type='author']">
                <xsl:sort select="id" order="ascending"/>
            </xsl:apply-templates>
        </fo:block>

        <fo:block space-before.optimum="0.1in" span="all">
            <xsl:for-each select="../aff">
                <xsl:sort select="@id" order="ascending"/>
                <fo:block>
                    <xsl:choose>
                        <xsl:when test="not(count(../aff) &gt; 1)">
                            <xsl:apply-templates select="./*[name()!='sup']"/>
                        </xsl:when>
                        <xsl:when test="not(sup)">
                            <fo:inline font-size="{$meta.font.size}" vertical-align="super">
                                <xsl:value-of select="position()"/>
                            </fo:inline>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:apply-templates />
                        </xsl:otherwise>
                    </xsl:choose>
                </fo:block>
            </xsl:for-each>
        </fo:block>
    </xsl:template>

    <xsl:template match="contrib-group" mode="back">
        <xsl:if test="contrib[@corresp = 'yes']">
            <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                <xsl:choose>
                    <xsl:when test="count(contrib[@corresp = 'yes']) = 1">
                        <xsl:text>Corresponding Author</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>Corresponding Authors</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </fo:block>

            <xsl:for-each select="contrib[@corresp = 'yes']">
                <fo:block font-size="{$body.font.size}" span="all">
                    <fo:block>
                        <xsl:apply-templates select="name/given-names"/>
                        <xsl:apply-templates select="name/surname"/>
                        <xsl:apply-templates select="degrees"/>
                    </fo:block>

                    <xsl:apply-templates select="role"/>

                    <xsl:choose>
                        <xsl:when test="address">
                            <xsl:apply-templates select="address"/>
                        </xsl:when>
                        <xsl:when test="../address">
                            <xsl:apply-templates select="../address"/>
                        </xsl:when>
                    </xsl:choose>
                </fo:block>
            </xsl:for-each>
        </xsl:if>
    </xsl:template>

    <xsl:template match="role">
        <fo:block>
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="xref">
        <xsl:choose>
            <xsl:when test="@ref-type='table-fn'">
                <fo:inline font-size="{$meta.font.size}" vertical-align="super">
                    <!-- TODO: use table label -->
                    <xsl:choose>
                        <xsl:when test="substring-after(@rid, 'fn') = '1'">*</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '2'">&#8224;</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '3'">&#8225;</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '4'">&#167;</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '5'">||</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '6'">&#182;</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '7'">#</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '8'">**</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '9'">&#8224;&#8224;</xsl:when>
                        <xsl:when test="substring-after(@rid, 'fn') = '10'">&#8225;&#8225;</xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="."/>
                        </xsl:otherwise>
                    </xsl:choose>
                </fo:inline>
            </xsl:when>
            <xsl:when test="@ref-type='aff' and not(count(//aff) &gt; 1)"/>
            <xsl:when test="@ref-type='aff'">
                <fo:inline font-size="{$meta.font.size}" vertical-align="super">
                    <xsl:value-of select="."/>
                    <xsl:if test="following-sibling::xref">,</xsl:if>
                </fo:inline>
            </xsl:when>
            <xsl:otherwise>
                <fo:basic-link internal-destination="{ancestor::article/front/article-meta/elocation-id}_{@rid}" color="blue">
                    <xsl:value-of select="."/>
                </fo:basic-link>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="address">
        <xsl:for-each select="institution">
            <fo:block>
                <xsl:value-of select="."/>
            </fo:block>
        </xsl:for-each>
        <xsl:for-each select="addr-line">
            <fo:block>
                <xsl:value-of select="."/>
            </fo:block>
        </xsl:for-each>
        <fo:block>
            <xsl:apply-templates select="country"/>
        </fo:block>
        <!--<xsl:if test="phone">
            <fo:block space-before.optimum="0.1in">phone: <xsl:value-of select="phone"/>
            </fo:block>
        </xsl:if>
        <xsl:if test="fax">
            <fo:block space-before.optimum="0.1in">fax: <xsl:value-of select="fax"/>
            </fo:block>
        </xsl:if>-->
        <xsl:if test="email">
            <fo:block>
                <fo:basic-link external-destination="url(mailto:{email})" color="blue">
                    <xsl:value-of select="email"/>
                </fo:basic-link>
            </fo:block>
        </xsl:if>
        <xsl:if test="uri">
            <fo:block>
                <fo:basic-link external-destination="url({uri})" color="blue">
                    <xsl:value-of select="uri"/>
                </fo:basic-link>
            </fo:block>
        </xsl:if>
    </xsl:template>

    <xsl:template match="title">
        <xsl:variable name="level">
            <xsl:value-of select="count(ancestor::sec)"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test=".=''"/>
            <xsl:when test="ancestor::abstract">
                <fo:inline font-weight="bold">
                    <xsl:value-of select="."/>
                    <xsl:text>: </xsl:text>
                </fo:inline>
            </xsl:when>
            <xsl:when test="ancestor::boxed-text"/>
            <xsl:when test="$level = 1">
                <fo:block space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:if test="preceding-sibling::sec">
                        <xsl:attribute name="space-before.optimum" value="0.25in"/>
                    </xsl:if>
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:when>
            <xsl:when test="$level = 2">
                <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:when>
            <xsl:when test="$level = 3">
                <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:when>
            <xsl:when test="$level = 4">
                <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$body.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:when>
            <xsl:when test="$level > 4">
                <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$body.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:when>
            <xsl:otherwise>
                <fo:block space-before.optimum="0.1in" space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:value-of select="."/>
                </fo:block>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="fn-group">
        <xsl:for-each select="fn">
            <fo:block span="all" space-before.optimum="0.25in">
                <fo:block font-size="{$heading.font.size}" font-weight="bold" keep-with-next.within-page="always">
                    <xsl:choose>
                        <xsl:when test="@fn-type='con'">
                            <xsl:text>Authors' Contributions</xsl:text>
                        </xsl:when>
                        <xsl:when test="@fn-type='conflict'">
                            <xsl:text>Conflicts of Interest</xsl:text>
                        </xsl:when>
                    </xsl:choose>
                </fo:block>
                <xsl:apply-templates/>
            </fo:block>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match="notes">
        <fo:block span="all">
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="country">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="ack">
        <fo:block space-before.optimum="0.25in" span="all">
            <fo:block font-size="{$heading.font.size}" font-weight="bold" keep-with-next.within-page="always">Acknowledgments</fo:block>
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="ref-list">
        <fo:block space-before.optimum="0.25in" span="all">
            <fo:block space-after.optimum="0.1in" font-size="{$heading.font.size}" font-family="{$heading.font.family}" font-weight="bold" keep-with-next.within-page="always">References</fo:block>
            <fo:block>
                <fo:list-block text-align="left">
                    <xsl:apply-templates select="ref/nlm-citation"/>
                </fo:list-block>
            </fo:block>
        </fo:block>
    </xsl:template>

    <xsl:template match="preformat">
        <fo:block font-size="{$small.font.size}" font-family="{$monospace.font.family}" white-space-collapse="false" white-space-treatment="preserve" keep-together="always">
            <xsl:call-template name="substitute">
                <xsl:with-param name="string" select="." />
                <xsl:with-param name="to">
                    <fo:block/>
                </xsl:with-param>
            </xsl:call-template>
        </fo:block>
    </xsl:template>

    <xsl:template match="speech">
        <fo:block font-size="{$body.font.size}" space-start="20pt" start-indent="20pt" space-end="20pt" end-indent="20pt">
            <xsl:apply-templates select="p"/>
            <xsl:text> </xsl:text>
            <xsl:if test="speaker">[<xsl:value-of select="speaker"/>]</xsl:if>
        </fo:block>
    </xsl:template>

    <xsl:template match="disp-quote">
        <fo:block font-size="{$body.font.size}" font-style="italic" space-start="20pt" start-indent="20pt" space-end="20pt" end-indent="20pt">
            <xsl:apply-templates select="p"/>
        </fo:block>
        <xsl:if test="attrib">
            <fo:block font-size="{$body.font.size}" space-start="20pt" start-indent="20pt" space-end="20pt" end-indent="20pt">
                <xsl:apply-templates select="attrib"/>
            </fo:block>
        </xsl:if>
    </xsl:template>

    <xsl:template match="list">
        <fo:list-block>
            <xsl:apply-templates/>
        </fo:list-block>
    </xsl:template>

    <xsl:template match="list-item">
        <fo:list-item>
            <fo:list-item-label end-indent="label-end()">
                <fo:block>
                    <xsl:choose>
                        <xsl:when test="../@list-type='alpha-lower'">
                            <xsl:number count="list-item" format="a."/>
                        </xsl:when>
                        <xsl:when test="../@list-type='alpha-upper'">
                            <xsl:number count="list-item" format="A."/>
                        </xsl:when>
                        <xsl:when test="../@list-type='roman-lower'">
                            <xsl:number count="list-item" format="i."/>
                        </xsl:when>
                        <xsl:when test="../@list-type='roman-upper'">
                            <xsl:number count="list-item" format="I."/>
                        </xsl:when>
                        <xsl:when test="../@list-type='order'">
                            <xsl:number count="list-item" format="1."/>
                        </xsl:when>
                        <xsl:otherwise>
                            <fo:block>&#x2022;</fo:block>
                        </xsl:otherwise>
                    </xsl:choose>
                </fo:block>
            </fo:list-item-label>
            <fo:list-item-body start-indent="body-start()">
                <xsl:apply-templates/>
            </fo:list-item-body>
        </fo:list-item>
    </xsl:template>

    <xsl:template match="ext-link">
        <xsl:choose>
            <xsl:when test="ancestor::source"/>
            <xsl:when test="ancestor::app and not(ancestor::table)">
                <xsl:text>[</xsl:text>
                <fo:basic-link external-destination="url({concat(/article/front/article-meta/self-uri/@xlink:href, @xlink:href)})" color="blue">
                    <xsl:choose>
                        <xsl:when test=".=''">
                            <xsl:value-of select="@xlink:href"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="."/>
                        </xsl:otherwise>
                    </xsl:choose>
                </fo:basic-link>
                <xsl:text>]</xsl:text>
            </xsl:when>
            <xsl:otherwise>
            <!--
            <fo:basic-link external-destination="url({@xlink:href})" color="blue">
            -->
                <xsl:choose>
                    <xsl:when test=".=''">
                        <xsl:value-of select="@xlink:href"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="."/>
                    </xsl:otherwise>
                </xsl:choose>
                <!--
            </fo:basic-link>
            -->
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="app-group/app">
        <fo:block span="all" id="{@id}">
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="gloss-group">
        <fo:block span="all">
            <xsl:apply-templates select="title"/>
            <xsl:apply-templates select="def-list"/>
        </fo:block>
    </xsl:template>

    <xsl:template match="def-list">
        <xsl:for-each select="def-item">
            <fo:block span="all">
                <fo:inline font-weight="bold" text-align="right">
                    <xsl:value-of select="term"/>:</fo:inline>
                <xsl:text> </xsl:text>
                <xsl:value-of select="def/p"/>
            </fo:block>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match="table-wrap">
        <fo:block id="{@id}" space-before.optimum="0.25in" space-after.optimum="0.25in" span="all">
<!--
            <fo:float>
-->
                <fo:block keep-with-next="always">
                    <xsl:apply-templates select="table"/>
                    <xsl:apply-templates select="table-wrap-foot/*"/>
                </fo:block>
                <fo:block space-before.optimum="0.2in" font-size="{$small.font.size}" keep-with-previous="always">
                    <xsl:if test="not(ancestor::boxed-text)">
                        <fo:inline font-weight="bold" keep-with-next="always">Table <xsl:value-of select="substring-after(@id,'table')"/>. </fo:inline>
                        <xsl:apply-templates select="caption/p"/>
                    </xsl:if>
                </fo:block>
<!--
            </fo:float>
-->
        </fo:block>
    </xsl:template>

    <xsl:template name="expand-colspan">
        <xsl:param name="colspan"/>
        <xsl:variable name="count">
            <xsl:value-of select="$colspan"/>
        </xsl:variable>
        <fo:table-column />
        <xsl:if test="$count > 1">
            <xsl:call-template name="expand-colspan">
                <xsl:with-param name="colspan" select="$count -1"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>

    <xsl:template match="table">
        <fo:table border-width="0.1mm" border-collapse="collapse" border-before-width.conditionality="retain" border-after-width.conditionality="retain" table-layout="auto" keep-with-previous="always" width="100%">
            <xsl:choose>
                <xsl:when test="col">
                    <xsl:call-template name="summation">
                        <xsl:with-param name="pNodes" select="col"/>
                    </xsl:call-template>
                    <xsl:apply-templates select="col"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:for-each select="tbody/tr[1]/*">
                        <xsl:call-template name="expand-colspan">
                            <xsl:with-param name="colspan" select="@colspan"/>
                        </xsl:call-template>
                    </xsl:for-each>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="thead">
                <xsl:apply-templates select="thead"/>
            </xsl:if>
            <xsl:choose>
                <xsl:when test="tbody">
                    <xsl:apply-templates select="tbody"/>
                </xsl:when>
                <xsl:otherwise>
                    <fo:table-body>
                        <xsl:apply-templates/>
                    </fo:table-body>
                </xsl:otherwise>
            </xsl:choose>
        </fo:table>
    </xsl:template>

    <xsl:template name="summation">
        <xsl:param name="pNodes" select="/.."/>
        <xsl:param name="result" select="0"/>
        <xsl:choose>
            <xsl:when test="$pNodes">
                <xsl:call-template name="summation">
                    <xsl:with-param name="pNodes" select="$pNodes[position()!=1]"/>
                    <xsl:with-param name="result" select="$result + $pNodes[1]/@width"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:for-each select="col">
                    <fo:table-column column-width="{round(@width div $result * 100)}%"/>
                </xsl:for-each>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="thead">
        <fo:table-header>
            <xsl:apply-templates/>
        </fo:table-header>
    </xsl:template>

    <xsl:template match="tbody">
        <fo:table-body>
            <xsl:apply-templates/>
        </fo:table-body>
    </xsl:template>

    <xsl:template match="tr">
        <xsl:element name="fo:table-row">
            <xsl:if test="not(ancestor::table-wrap[@position='anchor'])">
                <xsl:attribute name="keep-together.within-page">always</xsl:attribute>
                <xsl:attribute name="keep-with-next.within-page">always</xsl:attribute>
            </xsl:if>
            <xsl:if test="@valign='bottom'">
                <xsl:attribute name="display-align">after</xsl:attribute>
            </xsl:if>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="td|th">
        <xsl:element name="fo:table-cell">
            <xsl:if test="name()='th'">
                <xsl:attribute name="font-weight">bold</xsl:attribute>
            </xsl:if>
            <xsl:attribute name="border-width">thin</xsl:attribute>
            <xsl:choose>
                <xsl:when test="ancestor::thead and count(ancestor::tr/following-sibling::*) = 0">
                    <xsl:attribute name="border-bottom">solid</xsl:attribute>
                </xsl:when>
                <xsl:when test="ancestor::tbody[../@rules='rows']">
                    <xsl:attribute name="border-bottom">solid</xsl:attribute>
                </xsl:when>
                <xsl:when test="ancestor::tbody[../@rules='cols']">
                    <xsl:attribute name="border-right">solid</xsl:attribute>
                </xsl:when>
                <xsl:when test="ancestor::tbody[../@rules='all']">
                    <xsl:attribute name="border-bottom">solid</xsl:attribute>
                    <xsl:attribute name="border-left">solid</xsl:attribute>
                    <xsl:attribute name="border-right">solid</xsl:attribute>
                </xsl:when>
            </xsl:choose>
            <xsl:if test="@colspan">
                <xsl:attribute name="number-columns-spanned">
                    <xsl:value-of select="@colspan"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@rowspan">
                <xsl:attribute name="number-rows-spanned">
                    <xsl:value-of select="@rowspan"/>
                </xsl:attribute>
            </xsl:if>
            <!--
        <xsl:attribute name="keep-together.within-page">always</xsl:attribute>
        -->
            <xsl:element name="fo:block" font-size="{$small.font.size}">
                <xsl:attribute name="padding">2.5pt</xsl:attribute>
                <xsl:attribute name="margin-left">1pt</xsl:attribute>
                <xsl:attribute name="margin-right">1pt</xsl:attribute>
                <xsl:attribute name="hyphenate">true</xsl:attribute>
                <xsl:attribute name="text-align">
                    <xsl:choose>
                        <xsl:when test="normalize-space(@align)=''">left</xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="@align"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:attribute>
                <xsl:apply-templates/>
            </xsl:element>
            <!--
        <fo:block font-size="{$small.font.size}" padding="2.5pt" margin-left="1pt" margin-right="1pt" hyphenate="true" text-align="left">
            <xsl:apply-templates/>
        </fo:block>
        -->
        </xsl:element>
    </xsl:template>

    <xsl:template match="graphic">
        <fo:block display-align="center" text-align="center">
            <fo:external-graphic scaling="uniform" content-width="scale-to-fit" content-height="100%" width="100%" src="url(file:{@xlink:href})"/><!-- TODO: need cm measurement for width in FOP? -->
        </fo:block>
    </xsl:template>

    <xsl:template match="mml:math">
        <fo:block display-align="center" text-align="center">
            <fo:external-graphic scaling="uniform" content-width="scale-to-fit" content-height="100%" width="100%" src="url(file:{altimg/@href})"/>
        </fo:block>
    </xsl:template>

    <xsl:template match="fig">
        <fo:block id="{@id}" span="all" space-before.optimum="0.25in" space-after.optimum="0.25in">
<!--
            <fo:float>
-->
                <fo:block font-size="{$small.font.size}" keep-together.within-page="always">
                    <fo:block display-align="center" text-align="center" keep-with-next="always" space-after.optimum="0.1in">
                        <xsl:choose>
                            <xsl:when test="graphic">
                                <fo:external-graphic scaling="uniform" content-width="scale-to-fit" width="100%" src="url(file:{graphic/@xlink:href})"/>
                            </xsl:when>
                        </xsl:choose>
                    </fo:block>
                    <fo:inline font-weight="bold" keep-with-previous="always">Figure <xsl:value-of select="substring-after(@id,'figure')"/>. </fo:inline>
                    <xsl:apply-templates select="caption/*"/>
                    <xsl:apply-templates select="p"/>
                </fo:block>
<!--
            </fo:float>
-->
        </fo:block>
    </xsl:template>

    <xsl:template match="copyright-statement">
        <fo:block space-before.optimum="0.25in" span="all">
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="boxed-text">
        <fo:block id="{@id}" span="all">
<!--
            <fo:float>
-->
                <xsl:element name="fo:block">
                    <xsl:if test="not(@position='anchor')">
                        <xsl:attribute name="keep-together.within-page">always</xsl:attribute>
                    </xsl:if>
                    <xsl:attribute name="font-size">
                        <xsl:value-of select="$small.font.size"/>
                    </xsl:attribute>
                    <fo:block keep-with-next="always">
                        <fo:inline font-weight="bold">Textbox <xsl:value-of select="substring-after(@id,'box')"/>. </fo:inline>
                        <xsl:value-of select="title"/>
                    </fo:block>
                    <fo:block keep-with-previous="always">
                        <fo:table border-style="solid" border-width="1pt" border-before-width.conditionality="retain" border-after-width.conditionality="retain" table-layout="auto">
                            <fo:table-column/>
                            <fo:table-body>
                                <fo:table-row>
                                    <fo:table-cell padding="3pt">
                                        <xsl:choose>
                                            <xsl:when test="graphic">
                                                <fo:block display-align="center" text-align="center">
                                                    <xsl:choose>
                                                        <xsl:otherwise>
                                                            <fo:external-graphic scaling="uniform" content-width="scale-to-fit" content-height="100%" width="100%" src="url(file:{graphic/@xlink:href})"/>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </fo:block>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:apply-templates/>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </fo:table-cell>
                                </fo:table-row>
                            </fo:table-body>
                        </fo:table>
                    </fo:block>
                </xsl:element>
<!--
            </fo:float>
-->
        </fo:block>
    </xsl:template>

    <xsl:template match="pub-date[@pub-type='epub']">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="bold">
        <xsl:choose>
            <xsl:when test="ancestor::table-wrap or ancestor::fig or ancestor::boxed-text">
                <fo:inline font-size="{$small.font.size}" font-weight="bold">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:when>
            <xsl:otherwise>
                <fo:inline font-size="{$body.font.size}" font-weight="bold">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="italic">
        <xsl:choose>
            <xsl:when test="ancestor::table-wrap or ancestor::fig or ancestor::boxed-text">
                <fo:inline font-size="{$small.font.size}" font-style="italic">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:when>
            <xsl:otherwise>
                <fo:inline font-size="{$body.font.size}" font-style="italic">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="break">
        <fo:block font-size="{$body.font.size}" />
    </xsl:template>

    <xsl:template match="sec">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="abstract/sec">
        <fo:block>
            <xsl:apply-templates/>
        </fo:block>
    </xsl:template>

    <xsl:template match="p">
        <xsl:choose>
            <xsl:when test="ancestor::abstract or ancestor::list-item">
                <fo:block font-size="{$body.font.size}" span="all">
                    <xsl:apply-templates/>
                </fo:block>
            </xsl:when>
            <xsl:when test="count(child::*) = 1 and child::fig">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="parent::speech">
                <fo:inline font-size="{$body.font.size}" font-style="italic" orphans="2" widows="2">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:when>
            <xsl:when test="parent::sec or parent::app">
                <fo:block space-before.optimum="0.1in" font-size="{$body.font.size}" orphans="2" widows="2">
                    <xsl:apply-templates/>
                </fo:block>
            </xsl:when>
            <xsl:when test="ancestor::caption">
                <fo:inline font-size="{$small.font.size}" orphans="1" widows="1" keep-with-next="always" keep-with-previous.within-page="always" keep-together.within-page="always">
                    <xsl:apply-templates/>
                </fo:inline>
            </xsl:when>
            <xsl:when test="ancestor::table-wrap-foot">
<!--
                <fo:block font-size="{$small.font.size}" orphans="1" widows="1" keep-with-next="always" keep-with-previous.within-page="always" keep-together.within-page="always">
-->
                    <fo:inline font-size="{$meta.font.size}" vertical-align="super">
                        <xsl:variable name="fn-id" select="../@id"/>
                        <!-- TODO: Use labels -->
                        <xsl:choose>
                            <xsl:when test="substring-after($fn-id, 'fn') = '1'">*</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '2'">&#8224;</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '3'">&#8225;</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '4'">&#167;</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '5'">||</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '6'">&#182;</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '7'">#</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '8'">**</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '9'">&#8224;&#8224;</xsl:when>
                            <xsl:when test="substring-after($fn-id, 'fn') = '10'">&#8225;&#8225;</xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="//xref[@ref-type='table-fn' and @rid=$fn-id]"/>
                            </xsl:otherwise>
                        </xsl:choose>
                    </fo:inline>
                    <xsl:apply-templates/>
<!--
                </fo:block>
-->
            </xsl:when>
            <xsl:when test="ancestor::table-wrap or ancestor::fig or ancestor::boxed-text">
<!--
                <fo:block font-size="{$small.font.size}" orphans="1" widows="1" keep-with-next="always" keep-with-previous.within-page="always" keep-together.within-page="always">
-->
                    <xsl:apply-templates/>
<!--
                </fo:block>
-->
            </xsl:when>
            <xsl:otherwise>
                <fo:block space-before.optimum="0.1in" font-size="{$body.font.size}" orphans="2" widows="2">
                    <xsl:apply-templates/>
                </fo:block>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="sup">
        <fo:inline font-size="{$meta.font.size}" vertical-align="super">
            <xsl:apply-templates/>
        </fo:inline>
    </xsl:template>

    <xsl:template match="sub">
        <fo:inline font-size="{$meta.font.size}" vertical-align="sub">
            <xsl:apply-templates/>
        </fo:inline>
    </xsl:template>

    <xsl:template match="svg:svg">
        <fo:instream-foreign-object scaling="uniform" content-width="scale-to-fit" width="100%">
            <svg:svg width="800" height="600">
                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                    <xsl:if test="@viewBox">
                        <xsl:attribute name="viewBox" value="{@viewBox}"/>
                    </xsl:if>
                    <xsl:copy-of select="node()"/>
                </svg>
            </svg:svg>
        </fo:instream-foreign-object>
    </xsl:template>

    <xsl:template match="contrib">
        <xsl:apply-templates select="name/given-names"/>
        <xsl:apply-templates select="name/surname"/>
        <xsl:apply-templates select="xref[@ref-type='aff']"/>
        <!--<xsl:apply-templates select="degrees"/>-->
        <xsl:if test="position() != last()">
            <xsl:text>, </xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template match="given-names">
        <xsl:value-of select="."/>
        <xsl:text> </xsl:text>
    </xsl:template>
    <xsl:template match="surname">
        <xsl:value-of select="."/>
    </xsl:template>
    <xsl:template match="degrees">, <xsl:value-of select="."/>
    </xsl:template>

    <xsl:template match="institution|addr-line">
        <xsl:apply-templates/>
        <xsl:if test="count(following-sibling::*) &gt; 0">
            <xsl:text>, </xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template name="initials">
        <xsl:param name="name"/>
        <xsl:choose>
            <xsl:when test="contains($name,' ')">
                <xsl:call-template name="initials">
                    <xsl:with-param name="name" select="substring-before($name,' ')"/>
                </xsl:call-template>
                <xsl:call-template name="initials">
                    <xsl:with-param name="name" select="substring-after($name,' ')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:when test="string-length($name)&lt;3">
                <xsl:value-of select="$name"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="substring($name,1,1)"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="substitute">
        <xsl:param name="string" />
        <xsl:param name="from" select="'&#xA;'" />
        <xsl:param name="to" />
        <xsl:choose>
            <xsl:when test="contains($string, $from)">
                <xsl:value-of select="substring-before($string, $from)" />
                <xsl:copy-of select="$to" />
                <xsl:call-template name="substitute">
                    <xsl:with-param name="string"
                            select="substring-after($string, $from)" />
                    <xsl:with-param name="from" select="$from" />
                    <xsl:with-param name="to" select="$to" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$string" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


    <xsl:template name="xep-document-information">
        <rx:meta-info>
            <rx:meta-field name="author" value="{/article/front/article-meta/contrib-group/contrib[@contrib-type='author'][1]/name/surname}"/>
            <rx:meta-field name="title" value="{/article/front/article-meta/title}"/>
        </rx:meta-info>
    </xsl:template>

    <xsl:template match="/article/body/sec/title" mode="xep.outline">
        <rx:bookmark internal-destination="{@id}">
            <rx:bookmark-label>
                <xsl:value-of select="."/>
            </rx:bookmark-label>
        </rx:bookmark>
    </xsl:template>

    <xsl:template match="/article/body/sec/title" mode="fop.outline">
        <fox:outline internal-destination="{@id}">
            <fox:label>
                <xsl:value-of select="."/>
            </fox:label>
        </fox:outline>
    </xsl:template>

	    <xsl:template match="nlm-citation|related-article" name="nlm-citation">
	        <fo:list-item>

	            <fo:list-item-label end-indent="label-end()">
	                <fo:block id="{ancestor::article/front/article-meta/elocation-id}_{../@id}">
	                    <xsl:value-of select="../label"/>.</fo:block>
	            </fo:list-item-label>

	            <fo:list-item-body start-indent="body-start()">
	                <fo:block>

	                    <xsl:if test="person-group[@person-group-type!='editor'] or collab[@collab-type!='editors' or not(@collab-type)]">
	                        <xsl:for-each select="collab[@collab-type!='editors' or not(@collab-type)]">
	                            <xsl:value-of select="."/>
	                            <xsl:if test="position() != last()">
	                                <xsl:text>, </xsl:text>
	                            </xsl:if>
	                        </xsl:for-each>

	                        <xsl:if test="collab[@collab-type!='editors' or not(@collab-type)] and person-group[@person-group-type!='editor']">
	                            <xsl:text>, </xsl:text>
	                        </xsl:if>

	                        <xsl:for-each select="person-group[@person-group-type!='editor']/name">
	                            <xsl:if test="(position() &lt; 7 and last() &gt; 6) or (last() &lt; 7)">
	                                <xsl:value-of select="surname"/>
	                                <xsl:if test="given-names">
	                                    <xsl:text> </xsl:text>
	                                </xsl:if>
	                                <xsl:call-template name="initials">
	                                    <xsl:with-param name="name" select="given-names"/>
	                                </xsl:call-template>
	                            </xsl:if>

	                            <xsl:choose>
	                                <xsl:when test="boolean(suffix)">
	                                    <xsl:text> </xsl:text>
	                                    <xsl:value-of select="suffix"/>
	                                </xsl:when>
	                                <xsl:when test="position() != last() and (position() &lt; 6)">
	                                    <xsl:text>, </xsl:text>
	                                </xsl:when>
	                                <xsl:when test="position() = last() and (boolean(../etal) or last() &gt; 6)">
	                                    <xsl:text>, et al</xsl:text>
	                                </xsl:when>
	                            </xsl:choose>
	                        </xsl:for-each>
	                    </xsl:if>

	                    <xsl:if test="(person-group[@person-group-type!='editor'] or collab[@collab-type!='editors' or not(@collab-type)]) and (person-group[@person-group-type='editor'] or collab[@collab-type='editors'])">
	                        <xsl:choose>
	                            <xsl:when test="substring(article-title, string-length(article-title)) = '?'">
	                                <xsl:text>. </xsl:text>
	                                <xsl:value-of select="article-title"/>
	                                <xsl:text> In: </xsl:text>
	                            </xsl:when>
	                            <xsl:when test="normalize-space(article-title)!=''">
	                                <xsl:text>. </xsl:text>
	                                <xsl:value-of select="article-title"/>
	                                <xsl:text>. In: </xsl:text>
	                            </xsl:when>
	                            <xsl:otherwise>
	                                <xsl:text>. In: </xsl:text>
	                            </xsl:otherwise>
	                        </xsl:choose>
	                    </xsl:if>

	                    <xsl:if test="person-group[@person-group-type='editor'] or collab[@collab-type='editors']">
	                        <xsl:for-each select="collab[@collab-type='editors']">
	                            <xsl:value-of select="."/>
	                            <xsl:if test="position() != last()">
	                                <xsl:text>, </xsl:text>
	                            </xsl:if>
	                        </xsl:for-each>

	                        <xsl:if test="collab[@collab-type='editors'] and person-group[@person-group-type='editor']">
	                            <xsl:text>, </xsl:text>
	                        </xsl:if>

	                        <xsl:for-each select="person-group[@person-group-type='editor']/name">
	                            <xsl:if test="(position() &lt; 7 and last() &gt; 6) or (last() &lt; 7)">
	                                <xsl:value-of select="surname"/>
	                                <xsl:if test="given-names">
	                                    <xsl:text> </xsl:text>
	                                </xsl:if>
	                                <xsl:call-template name="initials">
	                                    <xsl:with-param name="name" select="given-names"/>
	                                </xsl:call-template>
	                            </xsl:if>

	                            <xsl:choose>
	                                <xsl:when test="boolean(suffix)">
	                                    <xsl:text> </xsl:text>
	                                    <xsl:value-of select="suffix"/>
	                                </xsl:when>
	                                <xsl:when test="position() != last() and (position() &lt; 6)">
	                                    <xsl:text>, </xsl:text>
	                                </xsl:when>
	                                <xsl:when test="position() = last() and (boolean(../etal) or last() &gt; 6)">
	                                    <xsl:text>, et al</xsl:text>
	                                </xsl:when>
	                            </xsl:choose>
	                        </xsl:for-each>

	                        <xsl:text>, editor</xsl:text>
	                        <xsl:if test="count(person-group[@person-group-type='editor']/name|collab[@collab-type='editors']) &gt; 1">s</xsl:if>
	                    </xsl:if>

	                    <xsl:choose>
	                        <xsl:when test="not(person-group or collab) and @citation-type!='web'"></xsl:when>
	                        <xsl:when test="not(person-group or collab) and @citation-type='web'"/>
	                        <xsl:otherwise>
	                            <xsl:text>. </xsl:text>
	                        </xsl:otherwise>
	                    </xsl:choose>

	                    <xsl:choose>
	                        <xsl:when test="(person-group[@person-group-type!='editor'] or collab[@collab-type!='editors' or not(@collab-type)]) and (person-group[@person-group-type='editor'] or collab[@collab-type='editors'])"/>
	                        <xsl:when test="substring(article-title, string-length(article-title)) = '?'">
	                            <xsl:value-of select="article-title"/>
	                            <xsl:text> </xsl:text>
	                        </xsl:when>
	                        <xsl:when test="normalize-space(article-title)='' or not(boolean(article-title))"/>
	                        <xsl:otherwise>
	                            <xsl:value-of select="article-title"/>
	                            <xsl:text>. </xsl:text>
	                        </xsl:otherwise>
	                    </xsl:choose>

	                    <xsl:if test="normalize-space(source)!=''">
	                        <xsl:if test="(@citation-type='confproc' or @citation-type='book') and normalize-space(article-title)!='' and not((person-group[@person-group-type!='editor'] or collab[@collab-type!='editors' or not(@collab-type)]) and (person-group[@person-group-type='editor'] or collab[@collab-type='editors']))">In:<xsl:text> </xsl:text>
	                        </xsl:if>

	                        <xsl:apply-templates select="source"/>

	                        <xsl:if test="boolean(edition)">
	                            <xsl:text>, </xsl:text>
	                            <xsl:value-of select="edition"/>
	                            <xsl:choose>
	                                <xsl:when test="edition='1'">st</xsl:when>
	                                <xsl:when test="edition='2'">nd</xsl:when>
	                                <xsl:when test="edition='3'">rd</xsl:when>
	                                <xsl:otherwise>th</xsl:otherwise>
	                            </xsl:choose>
	                            <xsl:text> edition</xsl:text>
	                        </xsl:if>

	                        <xsl:choose>
	                            <xsl:when test="normalize-space(source)=normalize-space(source/*)"/>
	                            <xsl:when test="@citation-type='other'"/>

	                            <xsl:when test="@citation-type='journal'"/>
	                            <xsl:when test="@citation-type='book' and not(publisher-loc)"/>
	                            <xsl:otherwise>.</xsl:otherwise>
	                        </xsl:choose>
	                    </xsl:if>

	                    <xsl:choose>
	                        <xsl:when test="boolean(publisher-loc) or boolean(publisher-name)">
	                            <xsl:if test="boolean(publisher-loc)">
	                                <xsl:text> </xsl:text>
	                                <xsl:value-of select="publisher-loc"/>
	                            </xsl:if>
	                            <xsl:if test="boolean(publisher-name)">
	                                <xsl:text>: </xsl:text>
	                                <xsl:value-of select="publisher-name"/>
	                            </xsl:if>
	                            <xsl:if test="boolean(year|month|day)">;</xsl:if>
	                        </xsl:when>
	                        <xsl:when test="@citation-type='confproc'" />
	                        <xsl:when test="@citation-type='book'" />
	                        <xsl:when test="@citation-type='other'">
	                            <xsl:text>. </xsl:text>
	                        </xsl:when>
	                        <xsl:otherwise>
	                            <xsl:text> </xsl:text>
	                        </xsl:otherwise>
	                    </xsl:choose>

	                    <xsl:if test="boolean(year) and not(@citation-type='book')">
	                        <xsl:text> </xsl:text>
	                        <xsl:value-of select="year"/>
	                    </xsl:if>
	                    <xsl:if test="boolean(month)">
	                        <xsl:text> </xsl:text>
	                        <xsl:choose>
	                            <xsl:when test="number(month)=1">Jan</xsl:when>
	                            <xsl:when test="number(month)=2">Feb</xsl:when>
	                            <xsl:when test="number(month)=3">Mar</xsl:when>
	                            <xsl:when test="number(month)=4">Apr</xsl:when>
	                            <xsl:when test="number(month)=5">May</xsl:when>
	                            <xsl:when test="number(month)=6">Jun</xsl:when>
	                            <xsl:when test="number(month)=7">Jul</xsl:when>
	                            <xsl:when test="number(month)=8">Aug</xsl:when>
	                            <xsl:when test="number(month)=9">Sep</xsl:when>
	                            <xsl:when test="number(month)=10">Oct</xsl:when>
	                            <xsl:when test="number(month)=11">Nov</xsl:when>
	                            <xsl:when test="number(month)=12">Dec</xsl:when>
	                        </xsl:choose>
	                    </xsl:if>
	                    <xsl:if test="boolean(season)">
	                        <xsl:text> </xsl:text>
	                        <xsl:value-of select="season"/>
	                    </xsl:if>
	                    <xsl:if test="boolean(day)">
	                        <xsl:text> </xsl:text>
	                        <xsl:value-of select="day"/>
	                    </xsl:if>

	                    <xsl:if test="boolean(year) and @citation-type='book'">
	                        <xsl:if test="boolean(day)">,</xsl:if>
	                        <xsl:text> </xsl:text>
	                        <xsl:value-of select="year"/>
	                    </xsl:if>
	                    <xsl:if test="(boolean(year) or boolean(month) or boolean(day) or boolean(season)) and @citation-type='web'">. </xsl:if>

	                    <xsl:if test="@citation-type='confproc'">
	                        <xsl:text> Presented at: </xsl:text>
	                        <xsl:value-of select="conf-name"/>
	                        <xsl:if test="boolean(conf-date)">
	                            <xsl:text>; </xsl:text>
	                            <xsl:value-of select="conf-date"/>
	                        </xsl:if>
	                        <xsl:if test="boolean(conf-loc)">
	                            <xsl:text>; </xsl:text>
	                            <xsl:value-of select="conf-loc"/>
	                        </xsl:if>
	                    </xsl:if>

	                    <xsl:if test="boolean(volume)">
	                        <xsl:choose>
	                            <xsl:when test="@citation-type='journal'">
	                                <xsl:text>;</xsl:text>
	                                <xsl:value-of select="volume"/>
	                            </xsl:when>
	                            <xsl:when test="@citation-type='book'">
	                                <xsl:text>, Vol. </xsl:text>
	                                <xsl:value-of select="volume"/>
	                            </xsl:when>
	                            <xsl:when test="not(boolean(@citation-type))">
	                                <xsl:text>;</xsl:text>
	                                <xsl:value-of select="volume"/>
	                            </xsl:when>
	                        </xsl:choose>
	                    </xsl:if>
	                    <xsl:if test="boolean(issue)">(<xsl:value-of select="issue"/>)</xsl:if>
	                    <xsl:if test="boolean(supplement)">
	                        <xsl:text> </xsl:text>
	                        <xsl:value-of select="supplement"/>
	                    </xsl:if>

	                    <xsl:if test="boolean(elocation-id)">
	                        <xsl:text>: </xsl:text>
	                        <xsl:value-of select="elocation-id"/>
	                    </xsl:if>

	                    <xsl:variable name="fplen" select="number(string-length(fpage))"/>
	                    <xsl:variable name="lplen" select="number(string-length(lpage))"/>

	                    <xsl:if test="(boolean(fpage) or boolean(lpage)) and (@citation-type='other' or @citation-type='web' or not(boolean(@citation-type)))">
	                        <xsl:text> p. </xsl:text>
	                        <xsl:value-of select="fpage"/>
	                        <xsl:if test="boolean(lpage)">-<xsl:if test="($lplen &lt; $fplen) and ($lplen &gt; 0)"><xsl:value-of select="substring(fpage, 1, $fplen - $lplen)"/>
	                            </xsl:if>
	                            <xsl:value-of select="lpage"/>
	                        </xsl:if>
	                        <xsl:text>.</xsl:text>
	                    </xsl:if>

	                    <xsl:if test="(boolean(fpage) or boolean(lpage)) and @citation-type='journal'">
	                        <xsl:text>:</xsl:text>
	                        <xsl:value-of select="fpage"/>
	                        <xsl:if test="boolean(lpage)">-<xsl:if test="($lplen &lt; $fplen) and ($lplen &gt; 0)"><xsl:value-of select="substring(fpage, 1, $fplen - $lplen)"/>
	                            </xsl:if>
	                            <xsl:value-of select="lpage"/>
	                        </xsl:if>
	                    </xsl:if>

	                    <xsl:if test="@citation-type='book'">
	                        <xsl:choose>
	                            <xsl:when test="(boolean(fpage) or boolean(lpage))">

	                                <xsl:text>:</xsl:text>
	                                <xsl:value-of select="fpage"/>
	                                <xsl:if test="boolean(lpage)">-<xsl:if test="($lplen &lt; $fplen) and ($lplen &gt; 0)"><xsl:value-of select="substring(fpage, 1, $fplen - $lplen)"/>
	                                    </xsl:if>
	                                    <xsl:value-of select="lpage"/>
	                                </xsl:if>
	                            </xsl:when>
	                            <xsl:otherwise>.</xsl:otherwise>
	                        </xsl:choose>
	                    </xsl:if>

	                    <xsl:choose>
	                        <xsl:when test="boolean(comment)">
	                            <xsl:text> </xsl:text>
	                            <xsl:apply-templates select="comment"/>.
		                </xsl:when>
	                        <xsl:when test="not(boolean(fpage) or boolean(lpage)) and @citation-type='book'"/>
	                        <xsl:when test="@citation-type='web'"/>
	                        <xsl:when test="@citation-type='discussion'"/>
	                        <xsl:when test="name()='related-article'"/>
	                        <xsl:otherwise>.</xsl:otherwise>
	                    </xsl:choose>

	                    <xsl:apply-templates select="source/ext-link" mode="refs"/>
	                    <xsl:apply-templates select="pub-id"/>
	<!--
	                <xsl:choose>
	                	<xsl:when test="@citation-type!='web' and boolean(source/ext-link)"><xsl:apply-templates select="pub-id[@pub-id-type!='doi']"/></xsl:when>
	                	<xsl:otherwise><xsl:apply-templates select="pub-id"/></xsl:otherwise>
	                </xsl:choose>
	-->
	                </fo:block>
	            </fo:list-item-body>
	        </fo:list-item>
	    </xsl:template>

	    <xsl:template match="comment">
	     <xsl:apply-templates mode="refs"/>
	     </xsl:template>

	    <xsl:template match="pub-id">
	        <xsl:choose>
	            <xsl:when test="@pub-id-type='pmid'">
	                <xsl:text> PubMed: </xsl:text>
	                <fo:basic-link external-destination="url(http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;list_uids={.}&amp;dopt=Abstract)" color="blue">
	                    <xsl:value-of select="."/>
	                </fo:basic-link>
	                <xsl:text>.</xsl:text>
	            </xsl:when>
	            <xsl:when test="@pub-id-type='doi'">
	                <xsl:text> DOI: </xsl:text>
	                <fo:basic-link external-destination="url(http://dx.doi.org/{.})" color="blue">
	                    <xsl:value-of select="."/>
	                </fo:basic-link>
	                <xsl:text>.</xsl:text>
	            </xsl:when>
	        </xsl:choose>
	    </xsl:template>

	    <xsl:template match="ext-link" mode="refs">
	        <xsl:choose>
	            <xsl:when test="ancestor::source and ancestor::nlm-citation[@citation-type!='journal']">
	                <xsl:text> URL: </xsl:text>
	                <fo:basic-link color="blue">
	                    <xsl:attribute name="external-destination">
	                        <xsl:value-of select="@xlink:href"/>
	                    </xsl:attribute>
	                    <xsl:choose>
	                        <xsl:when test=".=''">
	                            <xsl:value-of select="@xlink:href"/>
	                        </xsl:when>
	                        <xsl:otherwise>
	                            <xsl:value-of select="."/>
	                        </xsl:otherwise>
	                    </xsl:choose>
	                </fo:basic-link>
	                <xsl:if test="../../access-date">
	                    <xsl:text> [accessed </xsl:text>
	                    <xsl:value-of select="../../access-date"/>
	                    <xsl:text>]</xsl:text>
	                </xsl:if>
	            </xsl:when>
	            <xsl:when test="ancestor::comment">
	                <fo:basic-link color="blue" external-destination="url({@xlink:href})">
	                    <xsl:choose>
	                        <xsl:when test=".=''">
	                            <xsl:value-of select="@xlink:href"/>
	                        </xsl:when>
	                        <xsl:otherwise>
	                            <xsl:value-of select="."/>
	                        </xsl:otherwise>
	                    </xsl:choose>
	                </fo:basic-link>
	            </xsl:when>
	        </xsl:choose>
	    </xsl:template>

	    <xsl:template match="source">
	        <xsl:apply-templates/>
	    </xsl:template>

</xsl:stylesheet>
