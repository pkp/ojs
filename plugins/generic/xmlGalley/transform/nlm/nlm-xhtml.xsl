<?xml version="1.0" encoding="UTF-8"?>
	<!-- ============================================================= -->
	<!--  MODULE:    XSLT transform for NLM Journal Article            -->
	<!--             to OJS 2.x XHTML Article                          -->
	<!--  VERSION:   1.0                                               -->
	<!--  DATE:      August 2006                                       -->
	<!--                                                               -->
	<!--  Copyright 2006, MJ Suhonos <mj@robotninja.com>               -->
	<!--  Contributions by Alf Eaton <alf@hubmed.org>                  -->
	<!--                                                               -->
 	<!-- Distributed under the GNU GPL v2.							   -->
	<!-- For full terms see the file docs/COPYING.					   -->
	<!--                                                               -->
	<!-- ============================================================= -->

	<!-- ============================================================= -->
	<!--  TRANSFORM ELEMENT AND TOP-LEVEL SETTINGS                     -->
	<!-- ============================================================= -->

	<xsl:transform version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:util="http://dtd.nlm.nih.gov/xsl/util"
                xmlns:mml="http://www.w3.org/1998/Math/MathML"
                exclude-result-prefixes="util xsl xlink mml">

	<!-- ============================================================= -->
	<!-- INCLUDE THE NLM XHTML STYLESHEET                              -->
	<!-- ============================================================= -->

	<xsl:include href="viewnlm-v2.xsl"/>

	<xsl:output method="html"
	    		indent="yes"
	    		omit-xml-declaration="yes"
	    		standalone="no"
	    		encoding="utf-8" />


	<!-- ============================================================= -->

	<xsl:include href="functions.inc.xsl"/>

    <xsl:template match="/article">
		<!-- set javascript target so xref links open in same window -->
		<xsl:call-template name="window_name">
			<xsl:with-param name="name" select="'xrefwindow'"/>
		</xsl:call-template>

		<xsl:apply-templates select="front"/>
		<xsl:call-template name="make-body"/>
		<xsl:apply-templates select="back"/>
    </xsl:template>


	<!--============================================
		FRONT-MATTER ELEMENTS
	==============================================-->

	<xsl:template match="front">
		<div id="article-front" class="fm">

			<!-- article type -->
			<xsl:apply-templates select="article-meta/article-categories/subj-group"/>

			<h3 class="article-title">
				<xsl:value-of select="article-meta/title-group/article-title"/>
			</h3>

			<p class="author-list">
				<xsl:apply-templates select="article-meta/contrib-group/contrib[@contrib-type='author']">
					<xsl:sort select="@id"/>
				</xsl:apply-templates>						
	        </p>

			<p class="affiliations">
            	<xsl:apply-templates select="article-meta/aff">
                	<xsl:sort select="@id"/>
            	</xsl:apply-templates>
			</p>
			
			<div class="author-notes">
				<xsl:apply-templates select="article-meta/author-notes" mode="front"/>
			</div>

			<!-- corresponding author -->
			<!-- TODO: multiple corresp are supported in the DTD, we need to handle this -->
			<xsl:apply-templates select="article-meta/contrib-group/contrib[@contrib-type='author' and @corresp='yes' and (address or email)][1]" mode="corresp"/>

			<xsl:if test="article-meta/related-article">
		        <p id="related-articles">
					<div class="tl-main-part">Related articles:</div>
					<xsl:apply-templates select="article-meta/related-article"/>
				</p>
			</xsl:if>

			<xsl:apply-templates select="article-meta/abstract"/>

			<p class="reviewers">
				<xsl:apply-templates select="article-meta/contrib-group/contrib[@contrib-type='reviewer']"/>
	        </p>

			<p class="history">
				<xsl:apply-templates select="article-meta/history | ../pub-date[@pub-type='epub']" mode="front"/>
			</p>

			<!-- article citation info -->
			<xsl:call-template name="self-citation"/>

			<xsl:if test="article-meta/copyright-statement or article-meta/permissions/copyright-statement">
				<span id="copyright" class="tl-main-part">Copyright</span>
				<p>	
					<xsl:apply-templates select="article-meta/copyright-statement | article-meta/permissions/copyright-statement" mode="front"/>
				</p>
			</xsl:if>

			<xsl:apply-templates select="article-meta/kwd-group"/>

		</div>
	</xsl:template>

    <xsl:template name="self-citation">
        <div class="self-citation">

			<!-- one-line citation -->
            <abbr class="title" title="{journal-meta/journal-title}">
<!--                <xsl:value-of select="journal-meta/journal-id[@journal-id-type='nlm-ta' or @journal-id-type='pubmed'][1]"/> 	-->
				<xsl:value-of select="journal-meta/journal-id"/>
            </abbr>
            <xsl:text>. </xsl:text>

			<xsl:choose>
				<xsl:when test="article-meta/pub-date[@pub-type='epub']">
					<xsl:call-template name="date-format">
						<xsl:with-param name="date">
							<xsl:value-of select="format-number(article-meta/pub-date[@pub-type='epub']/year,'0000')"/>
							<xsl:value-of select="format-number(article-meta/pub-date[@pub-type='epub']/month,'00')"/>
							<xsl:value-of select="format-number(article-meta/pub-date[@pub-type='epub']/day,'00')"/>
						</xsl:with-param>
						<xsl:with-param name="type" select="'short'"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="date-format">
						<xsl:with-param name="date">
							<xsl:value-of select="format-number(article-meta/pub-date/year,'0000')"/>
							<xsl:value-of select="format-number(article-meta/pub-date/month,'00')"/>
							<xsl:value-of select="format-number(article-meta/pub-date/day,'00')"/>
						</xsl:with-param>
						<xsl:with-param name="type" select="'short'"/>
					</xsl:call-template>
				</xsl:otherwise>
            </xsl:choose>
            <xsl:text>; </xsl:text>

            <xsl:if test="article-meta/volume">
				<xsl:value-of select="article-meta/volume"/>
            </xsl:if>

            <xsl:if test="article-meta/issue">
                <xsl:text>(</xsl:text><xsl:value-of select="article-meta/issue"/><xsl:text>)</xsl:text>
            </xsl:if>

            <xsl:if test="article-meta/elocation-id">
                <xsl:text>: </xsl:text><xsl:value-of select="article-meta/elocation-id"/>
            </xsl:if>

			<xsl:if test="article-meta/article-id[@pub-id-type='doi']">
				<div id="doi">
		        	<abbr title="Digital Object Identifier">doi</abbr>
		        	<xsl:text>: </xsl:text><xsl:value-of select="article-meta/article-id[@pub-id-type='doi']"/>
				</div>
			</xsl:if>
        </div>
	  	<br/>

    </xsl:template>


	<!--============================================
		BACK-MATTER ELEMENTS
	==============================================-->

	<!-- force output of back matter into preferred order -->
    <xsl:template match="back">

		<hr class="part-rule"/>		

        <div id="article-back" class="bm">

			<!-- back matter title if there is one -->
		    <xsl:apply-templates select="title"/>

			<!-- supporting information -->
		    <xsl:apply-templates select="notes"/>

			<!-- footnotes -->
            <xsl:apply-templates select="fn-group"/>

			<!-- acknowledgements -->
            <xsl:apply-templates select="ack"/>

	<!-- Biography <bio>? only matches in mode=front -->

			<!-- appendices -->
            <xsl:apply-templates select="app-group"/>

			<!-- abbreviations -->
		    <xsl:apply-templates select="glossary"/>

			<!-- references -->
            <xsl:apply-templates select="ref-list"/>
        </div>
    </xsl:template>


	<!-- ============================================================= -->
	<!-- TEMPLATES THAT OVERLOAD THE NLM XHTML STYLESHEET              -->
	<!-- ============================================================= -->

	<xsl:template match="abbrev">
	  <xsl:choose>
	    <xsl:when test="@xlink:href">
	      <a>
	        <xsl:call-template name="make-href"/>
	        <xsl:call-template name="make-id"/>
	        <xsl:apply-templates/>
	      </a>
	    </xsl:when>
	    <xsl:otherwise>
	      <abbr class="capture-id">
	        <xsl:call-template name="make-id"/>
	        <xsl:apply-templates/>
	      </abbr>
	    </xsl:otherwise>
	  </xsl:choose>
	</xsl:template>

    <xsl:template match="abstract | trans-abstract">
	  	<br/>
		<hr class="part-rule"/>

		<h3 id="abstract">
			<!-- if there's no title, create one -->
			<xsl:call-template name="words-for-abstract-title"/>
		</h3>

		<xsl:call-template name="nl-1"/>

		<xsl:apply-templates select="*[not(self::title)]"/>
    </xsl:template>

	<!-- suppress TOC version of abstract if it exists -->
    <xsl:template match="abstract[@abstract-type='toc']"/>

	<xsl:template match="address">
		<address>
		    <xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</address>
	</xsl:template>

    <xsl:template match="addr-line | country | institution | phone | fax">
		<xsl:if test="local-name() = 'phone'">
			<xsl:text>Phone: </xsl:text>
		</xsl:if>
		<xsl:if test="local-name() = 'fax'">
			<xsl:text>Fax: </xsl:text>
		</xsl:if>

		<xsl:apply-templates/>

        <xsl:if test="position() != last() and following-sibling::*[.!='']">
            <xsl:text>, </xsl:text> 		<!-- separator: typically , or <br /> -->
        </xsl:if>
	</xsl:template>

	<xsl:template match="aff">
        <span id="#{@id}" class="aff">
			<xsl:apply-templates select="./text() | *[.!='']"/>

	        <xsl:if test="position() != last()">
	            <xsl:text>, </xsl:text> 	<!-- separator: typically , or <br /> -->
	        </xsl:if>
		</span>
	</xsl:template>

	<!-- TODO: pluralize appendices if there are multiple app-group/app -->
	<xsl:template match="app-group">
		<xsl:if test="position() = 1 and not(app/title)">
			<div class="tl-main-part" id="appendix">Appendix</div>
		</xsl:if>

		<xsl:apply-templates/>
	</xsl:template>

    <xsl:template match="bold">
        <strong>
            <xsl:apply-templates/>
        </strong>
    </xsl:template>

	<!-- XHTML table model elements -->
    <xsl:template match="col | colgroup | tbody">
        <xsl:copy>
			<!-- XHTML table model attributes -->
			<xsl:copy-of select="@abbr | @align | @axis | @border | @cellpadding | @cellspacing | @char | @charoff | @colspan | @frame | @headers | @rowspan | @rules | @scope | @span | @summary | @valign | @width"/>
            <xsl:apply-templates/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="contrib[@contrib-type='author']">
        <span class="author" id="author-{position()}">
			<xsl:apply-templates select="name | collab" mode="front"/>

            <xsl:if test="position() != last()">
                <xsl:text>; </xsl:text>
            </xsl:if>
        </span>
    </xsl:template>

    <xsl:template match="contrib[@contrib-type='author' and @corresp='yes' and (address or email)]" mode="corresp">
        <div class="tl-main-part">Corresponding author:</div>

		<div class="name">
			<xsl:apply-templates select="name" mode="corresp"/>
		</div>

		<xsl:apply-templates select="address"/>
		<xsl:apply-templates select="email"/>
    </xsl:template>

    <xsl:template match="contrib[@contrib-type='reviewer']">

		<xsl:if test="position() = 1">
			<xsl:text>Peer-reviewed by: </xsl:text>
		</xsl:if>

        <span class="author" id="author-{position()}">
			<xsl:apply-templates select="name" mode="corresp"/>

			<xsl:choose>
				<xsl:when test="position() = last() - 1">
                    <xsl:text> and </xsl:text>
				</xsl:when>
				<xsl:when test="position() != last()">
                    <xsl:text>, </xsl:text>
				</xsl:when>
			</xsl:choose>

		</span>
	</xsl:template>

	<!-- render proper XHTML for definition/glossary lists -->
    <xsl:template match="def">
        <dd>
            <xsl:apply-templates mode="def"/>
        </dd>
    </xsl:template>

    <xsl:template match="def-head"/> 	<!-- suppress def-head until we know what it does -->

    <xsl:template match="def-item">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="def-list">
		<xsl:apply-templates select="def-head"/>
        <dl>
            <xsl:apply-templates select="def-item"/>
        </dl>
    </xsl:template>

    <xsl:template match="disp-quote">
		<xsl:call-template name="nl-1"/>
        <blockquote class="disp-quote">
			<xsl:call-template name="make-id"/>
			<q>
            	<xsl:apply-templates select="*[local-name() != 'attrib']"/>
			</q>
	        <xsl:apply-templates select="attrib"/>
        </blockquote>
		<xsl:call-template name="nl-1"/>
    </xsl:template>

	<xsl:template match="email">
		<strong>E-mail: </strong>
	  	<xsl:call-template name="nl-1"/>
		<!-- this requires the functions.inc.xsl to be included to obfuscate the email address -->
		<xsl:call-template name="scramble_email">
			<xsl:with-param name="email" select="."/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="ext-link | uri">
		<xsl:variable name="link-content">
			<xsl:choose>
				<xsl:when test=".=''">
					<xsl:call-template name="spaceURLs">
						<xsl:with-param name="string">
							<xsl:value-of select="@xlink:href"/>
						</xsl:with-param>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="."/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<xsl:when test="@xlink:href">
				<!-- NB: target is deprecated in XHTML 1.0S -->
				<a target="_blank">
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:value-of select="$link-content"/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<xsl:value-of select="$link-content"/>
				</span>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>

	<xsl:template match="fig | fig-group | table-wrap | table-wrap-group">
        <div class="{local-name()}">
			<xsl:call-template name="make-id"/>

			<xsl:if test="@id">
				<a class="{local-name()}">
				<xsl:attribute name="name">
					<xsl:value-of select="@id"/>
				</xsl:attribute>
				</a>
			</xsl:if>

			<xsl:apply-templates select="." mode="put-at-end"/>
		</div>
	</xsl:template>

	<xsl:template match="fn">
	    <div class="fn">
			<xsl:call-template name="make-id"/>
	        <xsl:apply-templates/>
	    </div>
	</xsl:template>

    <xsl:template match="fn[@fn-type='conflict']">
	    <div class="fn">
			<xsl:call-template name="make-id"/>
			<span id="conflicts" class="tl-main-part">Conflicts</span>
		    <xsl:apply-templates/>
		</div>
    </xsl:template>

    <xsl:template match="fn[@fn-type='con']">
	    <div class="fn">
			<xsl:call-template name="make-id"/>
			<span class="tl-main-part">Contributions</span>
		    <xsl:apply-templates/>
		</div>
    </xsl:template>

	<xsl:template match="glyph-ref">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- improved history / date block for front -->
	<xsl:template match="history/date | pub-date[@pub-type='epub']" mode="front">

		<xsl:variable name="the-type">
			<xsl:choose>
				<xsl:when test="@date-type='accepted'">Accepted</xsl:when>
				<xsl:when test="@date-type='received'">Received</xsl:when>
				<xsl:when test="@date-type='rev-request'">Revision Requested</xsl:when>
				<xsl:when test="@date-type='rev-recd'">Revision Received</xsl:when>
				<xsl:when test="@pub-type='epub'">Published</xsl:when>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="$the-type">
			<span class="tl-default">
				<xsl:value-of select="$the-type"/>
				<xsl:text>: </xsl:text>
			</span>
		</xsl:if>

		<xsl:call-template name="date-format">
			<xsl:with-param name="date">
				<xsl:value-of select="format-number(./year,'0000')"/>
				<xsl:value-of select="format-number(./month,'00')"/>
				<xsl:value-of select="format-number(./day,'00')"/>
			</xsl:with-param>
			<xsl:with-param name="type" select="'long'"/>
		</xsl:call-template>

		<xsl:if test="position()!=last()">
			<!-- separator: typically , or ; -->
			<xsl:text>; </xsl:text>
		</xsl:if>

		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="label">
	    <span class="label">
		<!-- auto-generate figure numbers -->
		<!-- <xsl:text>Figure </xsl:text><xsl:number level="any" count="fig" format="1. "/> -->
		<!-- auto-generate table numbers -->
		<!-- <xsl:text>Table </xsl:text><xsl:number level="any" count="table-wrap/label" format="1. "/> -->
	        <xsl:apply-templates/>
			<xsl:text>. </xsl:text>
	    </span>
	</xsl:template>

	<xsl:template match="inline-formula//mml:math">
	  <xsl:choose>
	    <xsl:when test="@xlink:href">
	      <a>
	        <xsl:call-template name="make-href"/>
	        <xsl:call-template name="make-id"/>
	        <xsl:copy-of select="."/>
	      </a>
	    </xsl:when>
	    <xsl:otherwise>
	      <span class="capture-id">
	        <xsl:call-template name="make-id"/>
	        <xsl:copy-of select="."/>
	      </span>
	    </xsl:otherwise>
	  </xsl:choose>
	</xsl:template>

    <xsl:template match="monospace">
        <tt>
            <xsl:apply-templates/>
        </tt>
    </xsl:template>

	<xsl:template match="name" mode="corresp">
	    <xsl:apply-templates select="prefix"      mode="contrib"/>
	    <xsl:apply-templates select="given-names" mode="contrib"/>
	    <xsl:apply-templates select="surname"     mode="contrib"/>
	    <xsl:apply-templates select="suffix"      mode="contrib"/>
	</xsl:template>

	<!-- make p a little smarter; generate its own ids -->
	<xsl:template match="p">
	  <p>
		<xsl:choose>
            <xsl:when test="ancestor::body">
                <xsl:attribute name="id">
                    <xsl:text>para-</xsl:text>
                    <xsl:number level="any" count="/article/body//p"/>
                </xsl:attribute>
            </xsl:when>

			<xsl:otherwise>
				<xsl:call-template name="make-id"/>
			</xsl:otherwise>
		</xsl:choose>
	    <xsl:apply-templates/>
	  </p>
	  <xsl:call-template name="nl-1"/>
	</xsl:template>

    <!--	<xsl:template match="p[ancestor::abstract | ancestor::list | ancestor::fn | ancestor::fig | ancestor::caption | ancestor::speech | ancestor::disp-quote]" 	-->
	<xsl:template match="p[ancestor::list | ancestor::caption | ancestor::speech | ancestor::disp-quote]">
        <xsl:apply-templates/>
    </xsl:template>

	<xsl:template match="private-char">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="preformat">
	    <xsl:choose>
	        <xsl:when test="@preformat-type='code'">
	            <code>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</code>
	        </xsl:when>
	        <xsl:otherwise>
	            <pre>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</pre>
	        </xsl:otherwise>
	    </xsl:choose>
	</xsl:template>

	<!-- improved related-article block based on JMIR and journal citation -->
	<xsl:template match="related-article">

		<xsl:variable name="the-type">
            <xsl:choose>
                <xsl:when test="@related-article-type='correction-forward'">Corrected version</xsl:when>
                <xsl:when test="@related-article-type='correction-statement'">Correction statement</xsl:when>
                <xsl:when test="@related-article-type='corrected-article'">Original version</xsl:when>
                <xsl:when test="@related-article-type='commentary-article'">Comment on</xsl:when>
                <xsl:when test="@related-article-type='commentary'">Comment in</xsl:when>
                <xsl:when test="@related-article-type='companion'">Companion</xsl:when>
            </xsl:choose>
		</xsl:variable>

		<div class="gen">
			<xsl:if test="$the-type">
				<xsl:value-of select="$the-type"/>
				<xsl:text>: </xsl:text>
			</xsl:if>
			<xsl:call-template name="journal-citation"/>

            <xsl:if test="boolean(@xlink:href)">
				<xsl:text> </xsl:text>
				<a target="_blank" href="{@xlink:href}">
					<xsl:call-template name="make-id"/>
					<xsl:value-of select="@xlink:href"/>
				</a>
			</xsl:if>
		</div>

	</xsl:template>

    <xsl:template match="sec[ancestor::body]">
		<div class="sec">
			<!-- auto-gen body section IDs -->
            <xsl:attribute name="id">
                <xsl:choose>
                    <xsl:when test="@sec-type">
                        <xsl:value-of select="@sec-type"/>
                    </xsl:when>
                    <xsl:when test="@id">
                        <xsl:value-of select="@id"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>section-</xsl:text>
                        <xsl:number level="multiple" count="body//sec"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>

			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="speaker">
        <strong class="speaker">
			<xsl:call-template name="make-id"/>
			<xsl:text> [</xsl:text><xsl:apply-templates/><xsl:text>] </xsl:text>
        </strong>
    </xsl:template>

    <xsl:template match="speech">
        <blockquote class="speech">
		    <xsl:call-template name="make-id"/>
		    <xsl:call-template name="nl-1"/>
	        <xsl:apply-templates select="speaker"/>
			<q>
            	<xsl:apply-templates select="*[local-name() != 'speaker']"/>
			</q>
		    <xsl:call-template name="nl-1"/>
        </blockquote>
    </xsl:template>

    <xsl:template match="strike">
        <del>
            <xsl:apply-templates/>
        </del>
    </xsl:template>

	<xsl:template match="subj-group">
		<xsl:if test="contains(translate(@subj-group-type, 'QWERTYUIOPASDFGHJKLZXCVBNM ', 'qwertyuiopasdfghjklzxcvbnm-'), 'article-type')">
			<div class="article-type">
				<xsl:value-of select="subject"/>
			</div>
		</xsl:if>
	</xsl:template>

	<!-- suppress table-wrap ID -->
	<xsl:template match="table-wrap/@id"/>

    <xsl:template match="term">
        <dt>
            <xsl:apply-templates/>
        </dt>
    </xsl:template>

    <xsl:template match="title[ancestor::body and ancestor::sec]">
		<xsl:variable name="level">
			<xsl:choose>
				<xsl:when test="count(ancestor::sec) &gt; 4">6</xsl:when>
				<xsl:otherwise><xsl:value-of select="count(ancestor::sec)+2"/></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

        <xsl:element name="h{$level}">
			<xsl:attribute name="class">sec-title</xsl:attribute>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>

<!-- 	<xsl:template match="title[ancestor::fig or ancestor::table-wrap or ancestor::abstract or ancestor::app or ancestor::boxed-text]">	 -->
	<xsl:template match="title[ancestor::gloss-group]">
		<div class="tl-main-part"><xsl:apply-templates/></div>
	</xsl:template>

	<!-- these improve the NLM xref templates (both contrib and not) -->
	<xsl:template match="xref[@ref-type!='']" mode="contrib">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="xref[@ref-type='aff'] 
						| xref[@ref-type='fn'] 
						| xref[@ref-type='author-notes'] 
						| xref[@ref-type='table-fn']">
		<span class="xref">
			<xsl:call-template name="make-id"/>

			<sup><i>
				<!-- if immediately-preceding sibling was an xref, punctuate
					(otherwise assume desired punctuation is in the source).-->
				<xsl:if test="local-name(preceding-sibling::node()[1])='xref'">
					<span class="gen"><xsl:text>, </xsl:text></span>
				</xsl:if>
				<a target="xrefwindow" href="#{@rid}">
					<xsl:value-of select="normalize-space(.)"/>
				</a>
			</i></sup>
		</span>
    </xsl:template>

	<xsl:template match="xref[@ref-type='bibr']">
		<cite class="xref">
			<xsl:call-template name="make-id"/>
			<!-- if immediately-preceding sibling was an xref, punctuate
				(otherwise assume desired punctuation is in the source).-->
			<xsl:if test="local-name(preceding-sibling::node()[1])='xref'">
				<span class="gen"><xsl:text>, </xsl:text></span>
			</xsl:if>
			<a target="xrefwindow" href="#{@rid}">
				<!-- <xsl:value-of select="/article/back/ref-list/ref[@id=$ref-id]/label"/> -->
				<xsl:apply-templates/>
			</a>
		</cite>
	</xsl:template>

	<!-- override to display the content of fig/table refs rather than ID -->
	<xsl:template match="xref[@ref-type='fig'] | xref[@ref-type='table']">
	  <span class="xref">
	    <xsl:call-template name="make-id"/>
	    <a target="xrefwindow" href="#{@rid}">
	      <xsl:value-of select="."/>
	    </a>
	  </span>
	</xsl:template>

	<!-- suppress superscipting hyphen between xrefs (at least for bibr) -->
	<xsl:template match="text()[normalize-space(.)='-']">
		<xsl:text>-</xsl:text>
	</xsl:template>


	<!--============================================
		NLM-CITATION MODEL ELEMENTS
	==============================================-->

	<xsl:template match="collab">
		<xsl:apply-templates select="." mode="book"/>		
	</xsl:template>

	<!-- special handling for citation links -->
	<xsl:template match="comment/ext-link" mode="none">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="comment/ext-link" mode="nscitation">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="comment/ext-link" mode="citation">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="comment/ext-link">

		<xsl:variable name="href">
			<xsl:choose>
				<xsl:when test="ancestor::nlm-citation[@citation-type!='journal'] and not(contains(., 'www.webcitation.org'))">
					<xsl:text>http://www.webcitation.org/query?url=</xsl:text>
					<xsl:call-template name="url-encode">
						<xsl:with-param name="str" select="@xlink:href"/>
					</xsl:call-template>
					<!-- TODO: webcite link should include formatted access-date -->
					<!--
					<xsl:text>&amp;refdoi=</xsl:text>
					<xsl:value-of select="/article/front/article-meta/article-id[@pub-id-type='doi']"/>
					-->
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@xlink:href"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:text> [</xsl:text>
		<!-- NB: target is deprecated in XHTML 1.0S -->
		<a target="_blank">
			<xsl:call-template name="make-id"/>
			<xsl:attribute name="href">
				<xsl:value-of select="$href"/>
			</xsl:attribute>
			<xsl:choose>
				<xsl:when test="contains($href, 'www.webcitation.org') or contains(., 'www.webcitation.org')">
					<xsl:text>WebCite Cache</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>Full Text</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</a>
		<xsl:text>] </xsl:text>

	</xsl:template>

	<!-- TODO: improved but still imperfect rendering for book names -->
	<xsl:template match="given-names">
		<xsl:call-template name="initials">
			<xsl:with-param name="name" select="."/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="name" mode="nscitation">
	  <xsl:value-of select="surname"/>
	  <xsl:text>, </xsl:text>
      <xsl:call-template name="initials">
		<xsl:with-param name="name" select="given-names"/>
	  </xsl:call-template>
	  <xsl:text>. </xsl:text>
	</xsl:template>

	<xsl:template match="surname">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- fix for the broken preview template -->
	<xsl:template match="person-group" mode="book">
	  <xsl:choose>

	    <xsl:when test="@person-group-type='editor'
	                  or @person-group-type='assignee'
	                  or @person-group-type='translator'
	                  or @person-group-type='transed'
	                  or @person-group-type='guest-editor'
	                  or @person-group-type='compiler'
	                  or @person-group-type='inventor'
	                  or @person-group-type='allauthors'">

	      <xsl:call-template name="make-persons-in-mode"/>
	      <xsl:call-template name="choose-person-type-string"/>
	      <xsl:call-template name="choose-person-group-end-punct"/>

	    </xsl:when>

	    <xsl:otherwise>
	      <xsl:apply-templates mode="book"/>
	    </xsl:otherwise>

	  </xsl:choose>
	</xsl:template>

	<!-- this is an improved citation rendering block 
		 based on:  match="pub-id[@pub-id-type='pmid']" mode="nscitation"
		 unlike the preview XSL, it will suppress unrecognized pub-id-types -->

	<xsl:template match="pub-id" mode="nscitation">
		<xsl:apply-templates select="." mode="none"/>
	</xsl:template>

	<xsl:template match="pub-id" mode="none">

		<xsl:variable name="pub-id" select="."/>
		<xsl:variable name="href">
			<xsl:choose>
				<xsl:when test="@pub-id-type='medline' or @pub-id-type='pmid'">
					<xsl:value-of select="'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;dopt=abstract&amp;list_uids='"/>
				</xsl:when>
				<!--
				<xsl:when test="@pub-id-type='pmid'">
					<xsl:value-of select="'http://www.pubmedcentral.nih.gov/articlerender.fcgi?tool=pubmed&amp;pubmedid='"/>
				</xsl:when>
				-->
				<xsl:when test="@pub-id-type='doi'">
					<xsl:value-of select="'http://dx.doi.org/'"/>
				</xsl:when>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="$href!=''">
			<xsl:text> [</xsl:text>
			<a>
				<xsl:attribute name="href">
					<xsl:value-of select="concat($href,$pub-id)"/>
				</xsl:attribute>
				<xsl:attribute name="target">
					<xsl:text>_new</xsl:text>
				</xsl:attribute>

				<xsl:choose>
					<xsl:when test="@pub-id-type='medline' or @pub-id-type='pmid'">
						<xsl:text>PubMed</xsl:text>
					</xsl:when>
					<!--
					<xsl:when test="@pub-id-type='pmid'">
						<xsl:text>PubMed Central</xsl:text>
					</xsl:when>
					-->
					<xsl:when test="@pub-id-type='doi'">
						<xsl:text>CrossRef</xsl:text>
					</xsl:when>
				</xsl:choose>
			</a>
			<xsl:text>] </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- overloaded ref-list and ref matching; TODO: remove table? -->
	<xsl:template match="ref-list">

	  <xsl:if test="position()>1">
	    <hr class="section-rule"/>
	  </xsl:if>

	  <xsl:choose>
	    <xsl:when test="not(title)">
	      <span class="tl-main-part">References</span>
	      <xsl:call-template name="nl-1"/>
	    </xsl:when>
	    <xsl:otherwise>
	      <xsl:apply-templates select="title"/>
	    </xsl:otherwise>
	  </xsl:choose>

	  <table width="100%" class="bm">
		<xsl:call-template name="table-setup-l-narrow"/>

	    <xsl:apply-templates select="*[name()!='title']"/>
	  </table>

	  <xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="ref">
	  <tr>
	    <xsl:call-template name="nl-1"/>

	    <td id="{@id}" valign="top" align="right">
			<xsl:choose>
				<xsl:when test="not(label)">
				    <strong><i><xsl:value-of select="position()"/><xsl:text>. </xsl:text></i></strong>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="label"/>
				</xsl:otherwise>
			</xsl:choose>
	    </td>
	    <xsl:call-template name="nl-1"/>

	    <td valign="top">
	      <xsl:apply-templates select="citation|nlm-citation"/>
	    </td>
	    <xsl:call-template name="nl-1"/>
	  </tr>
	  <xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="ref/label">
	    <strong><i><xsl:apply-templates/><xsl:text>. </xsl:text></i></strong>
	</xsl:template>


	<!-- journal citation rendering based on JMIR -->

	<!-- books: display edition; check numbering (1st, 2nd, etc) -->
	<!-- books/confproc: display publisher name/location; consider "presented at:" -->

	<xsl:template match="ref/nlm-citation[@citation-type='journal']" name="journal-citation">

		<!-- TODO: do we need this for journal articles? -->
		<!--  <xsl:variable name="augroupcount" select="count(person-group) + count(collab)"/>  -->

		<!-- TODO: convert to initials, include etal for >6 authors -->
		<xsl:apply-templates select="person-group[@person-group-type='author']"
		                     mode="nscitation"/>

		<xsl:apply-templates select="collab" mode="none"/>

		<!-- TODO: check last character is punctuation -->
		<xsl:apply-templates select="article-title"
		                     mode="nscitation"/>

		<xsl:apply-templates select="source"
		                     mode="nscitation"/>

		<xsl:apply-templates select="year"
                     		 mode="nscitation"/>
		<xsl:apply-templates select="month | time-stamp | season | access-date"
		                     mode="book"/>

		<xsl:apply-templates select="volume | issue"
		                     mode="none"/>

		<xsl:apply-templates select="fpage"
		                     mode="none"/>

		<xsl:apply-templates select="comment" mode="journal"/>

		<xsl:apply-templates select="pub-id" mode="none"/>
	</xsl:template>

	<xsl:template match="comment" mode="journal">
		<xsl:apply-templates select="." mode="citation"/>
	</xsl:template>

	<xsl:template match="comment" mode="citation">
		<!-- only output a period if there is text in the comment -->
		<xsl:choose>
			<xsl:when test="not(self::node()='.') and self::text()">
			    <xsl:text> </xsl:text>
			    <xsl:apply-templates/>
			    <xsl:text>. </xsl:text>
			</xsl:when>
			<xsl:when test="not(self::node()='.')">
			    <xsl:text> </xsl:text>
			    <xsl:apply-templates/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- strip superfluous . from article-title and source elements -->
	<xsl:template match="text()[parent::article-title or parent::source]">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>

		<xsl:choose>
			<xsl:when test="contains('.', $last-char)">
				<xsl:value-of select="substring(normalize-space(.), 1, string-length(.)-1)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- ============================================================= -->
	<!-- DEFAULT TEMPLATE - OUTPUT NODES THAT AREN'T MATCHED ELSEWHERE -->
	<!-- ============================================================= -->

	<xsl:template match="*">
		<!--
	    <xsl:message>
	        <xsl:value-of select="name(.)"/>
	        <xsl:text> encountered</xsl:text>
	        <xsl:if test="parent::*">
	            <xsl:text> in </xsl:text>
	            <xsl:value-of select="name(parent::*)"/>
	        </xsl:if>
	        <xsl:text>, but no template matches.</xsl:text>
	    </xsl:message>
		-->
	    <div style="color:red">
	        <xsl:text>&lt;</xsl:text>
	        <xsl:value-of select="name(.)"/>
	        <xsl:text>&gt;</xsl:text>
	        <xsl:apply-templates/>
	        <xsl:text>&lt;/</xsl:text>
	        <xsl:value-of select="name(.)"/>
	        <xsl:text>&gt;</xsl:text>
	    </div>
	</xsl:template>

</xsl:transform>