<?xml version="1.0"?>
<!-- ============================================================= -->
<!--  MODULE:    HTML View of NLM Journal Article                  -->
<!--  VERSION:   2.30                                           -->
<!--  DATE:      June 2007                                      -->
<!--                                                               -->
<!-- ============================================================= -->

<!-- ============================================================= -->
<!--  SYSTEM:    NCBI Archiving and Interchange Journal Articles   -->
<!--                                                               -->
<!--  PURPOSE:   Provide an HTML preview of a journal article,     -->
<!--             in a form suitable for reading.                   -->
<!--                                                               -->
<!--  CONTAINS:  Documentation:                                    -->
<!--               D1) Change history                              -->
<!--               D2) Structure of this transform                 -->
<!--               D3) Design of the output                        -->
<!--               D4) Constraints on the input                    -->
<!--                                                               -->
<!--             Infrastructure:                                   -->
<!--               1) Transform element and top-level settings     -->
<!--                  including parameters, variables, keys, and   -->
<!--                  look-up tables                               -->
<!--               2) Root template                                -->
<!--               3) Document template (and make-a-piece)         -->
<!--               4) Utility templates                            -->
<!--               5) Formatting elements                          -->
<!--               6) Suppressed elements                          -->
<!--             Called templates for article parts:               -->
<!--               7) make-html-header                             -->
<!--               8) make-front                                   -->
<!--               9) make-body                                    -->
<!--              10) make-back                                    -->
<!--              11) make-post-publication                        -->
<!--              12) make-end-metadata                            -->
<!--             Narrative content and common structures:          -->
<!--              13) paragraph                                    -->
<!--              14) section                                      -->
<!--              15) list                                         -->
<!--              16) display-quote                                -->
<!--              17) speech                                       -->
<!--              18) statement                                    -->
<!--              19) verse-group                                  -->
<!--              20) boxed-text                                   -->
<!--              21) preformat                                    -->
<!--              22) supplementary-material                       -->
<!--              23) display-formula and chem-struct-wrapper      -->
<!--             Inline Elements:                                  -->
<!--              24) formatting elements                          -->
<!--              25) semantic elements                            -->
<!--              26) break and horizontal rule                    -->
<!--             Display Objects:                                  -->
<!--              27) chem-struct                                  -->
<!--              28) tex-math and math                            -->
<!--              29) graphic and media                            -->
<!--              30) array                                        -->
<!--              31) captioning                                   -->
<!--              32) figure (mode put-at-end)                     -->
<!--              33) table-wrap (mode put-at-end)                 -->
<!--             Front mode:                                       -->
<!--              34) journal-meta                                 -->
<!--              35) article-meta                                 -->
<!--              36) title-group                                  -->
<!--              37) the parts of contrib element                 -->
<!--             Back (no mode):                                   -->
<!--              38) Acknowledgements                             -->
<!--              39) Appendix                                     -->
<!--              40) Footnote-group and fn                        -->
<!--              41) Notes                                        -->
<!--              42) Glossary                                     -->
<!--             Links:                                            -->
<!--              43) Target of a reference                        -->
<!--              44) xref                                         -->
<!--              45) external links                               -->
<!--             Titles:                                           -->
<!--              46) Main article divisions                       -->
<!--              47) First-level subdivisions and default         -->
<!--              48) make-abstract-title                          -->
<!--             Unmoded data elements:                            -->
<!--              49) Miscellaneous (epage, series, etc.)          -->
<!--              50) Parts of a date                              -->
<!--              51) Parts of a name                              -->
<!--             Citation and nlm-citation (NLM templates):        -->
<!--              52) ref-list                                     -->
<!--              53) ref                                          -->
<!--              54) citation                                     -->
<!--              55) nlm-citation                                 -->
<!--              56) citation sub-parts                           -->
<!--              57) citation-tag-ends                            -->
<!--                                                               -->
<!--  PROCESSOR DEPENDENCIES:                                      -->
<!--             None: standard XSLT 1.0                           -->
<!--             Tested under Apache Xalan 2.5.1                   -->
<!--                                                               -->
<!--  COMPONENTS REQUIRED:                                         -->
<!--             1) This stylesheet                                -->
<!--             2) CSS styles defined in ViewNLM.css              -->
<!--                                                               -->
<!--  INPUT:     An XML document valid with the NLM                -->
<!--             Publishing or Archive and Interchange DTD.          -->
<!--                                                               -->
<!--  OUTPUT:    An HTML preview of the article.                   -->
<!--                                                               -->
<!--  ORIGINAL CREATION DATE:                                      -->
<!--             October 2003                                      -->
<!--                                                               -->
<!-- ============================================================= -->

<!-- ============================================================= -->
<!--  D1) STYLESHEET VERSION / CHANGE HISTORY                      -->
<!-- =============================================================

 No.  CHANGE (reason for / description)  	     VERSION 	DATE

  6.  Added user-requested improvements	v2.30		2007-06-01
  	- Added custom-meta-wrap
  	- Corrected behavior of ext-link, uri, xref, label, list, table, def-list
  	- Defined missing classes: monospace, overline, take-note
  	- Mode nscitation to not write out any punctuation beyond name list
  	- Named-content now highlighted in flow of text

  5.  Changed documentation style from comments
      to (example) doc:documentation/doc:p      v02.04 2005-08-10

  4.  Revised to produce XHTML.                 v02.03 2005-08-10

  3.  Revised to accommodate DTD changes        v02.02 2005-08-22

      - Added mml namespace declaration for MathML
      - Changed the namespace prefix for the utilities
        internal to this transform, from "m" to "util",
        [to avoid confusion with the MathML use of "m",
        which the NLM DTD overrides to "mml" for the sake
        of backwards compatibility].

  2.  Revised to fix typos and infelicities.    v02.01 2005-08-08

      - Reorganized transform for easier reading
          e.g., consolidated mode="none" templates (applied to loose
        bibref models when XML source doesn't provide punctuation).

      - Replaced xsl:text making newlines with a call-template,
        for easier reading and so these can be suppressed
        (conditionally or unconditionally) if desired. Also, now
        a search on xsl:text will find only (real) generated text.

      - Diagnosed issue with display of title-in-left-column,
        content-in-right-column in IE, Firefox.
      - Corrected behavior of many small parts, e.g.,
        self-uri, product/contrib and product/collab, etc.
      - Regularized the mode names and usage for front and back.
      - Set up structure anticipating sub-article and response
        (both of which have same top-level parts as article,
        and are themselves -within- article).
      - Improved punctuation and display of xrefs
        (fn, table-fn, bibr)
      - Corrected behavior of generated text on abstract types.
      - In templates for author-notes and descendents, made
        provision for the presence of a title/label.
      - In template for author name, corrected "pref" to "prefix"
      - In template for speech, corrected logic on excluding speaker
      - Tightened up the test for mode="none" on citation/ref.

      - Changed xsl:output indent to yes (was no)
      - Changed xsl:strip-space element list (was *)
      - Added xsl:preserve-space element list

      - Added doctype calls for Strict HTML DTD (in prep for
        producing XHTML).

  1.  v0.1.                                     v01 2003-11-03

      Based on transform downloaded from NCBI website 10/23/03.

      This version (v0.1) produces readable output
      for a sample set of publishing and archiving articles.
      There is more to do with respect to scope (e.g., the
      permissible variations in content allowed by the
      Archiving DTD).

                                                                   -->
<!-- ============================================================= -->

<!-- ============================================================= -->
<!--  D2) STRUCTURE OF THIS TRANSFORM                              -->
<!-- ============================================================= -->

<!--  The main transform is organized into sections as enumerated
      above.

      It is sometimes preferable to separate element templates,
      named templates, and moded templates. In this case, however,
      that would reduce rather than increase legibility. It is
      easier to follow what the front-matter template is doing
      when the named templates and modes it uses are ready to hand;
      similarly for the back matter and, especially, the references.

      The design gives considerable importance to clarity and
      maintainability, resulting in conventions such as generally
      giving each element type its own template, in preference to
      more concise alternatives.

      In addition, the transform produces explicit new-lines
      to improve legibility of the serialized output. (These are
      in the form <xsl:call-template name="nl-2"/>. )

      This transform is commented to explain the mappings used,
      and (intermittently) the content combinations being handled.
                                                                   -->

<!-- ============================================================= -->
<!--  D3) DESIGN OF THE OUTPUT                                     -->
<!-- ============================================================= -->

<!-- Purpose: An HTML preview of an article, to assist the
              author or editor in finalizing and approving
              the tagging.

     Characteristics arising from purpose:

              - link/target pairs display the ID as a label,
                rather than generating an explicit number.
              - the running-head text, if any, is displayed
                below the title


     Organization of Display:

     A. HTML setup
       1. HTML Metadata

     B. Article

       1. Front: Publication metadata (journal and article)

       2. Content metadata:
                 Title
                 Contributor(s)
                 Abstract(s)

       3. Body:  Sections &c.

       4. Back:  a) From XML "back": acknowledgements,
                   glossary, references, and back-matter notes.

                 b) Figs-and-tables. These are collected from
                    throughout the front, body, and back.

                 c) Content metadata for retrieval - keywords,
                    subject categories. &c.

     C. Sub-article or response, if any

        Has the same 5-part structure as "B. Article".


     Typographic notes:

     A red rule separates the four document divisions listed
     above for article. The major divisions -within- those parts
     are separated by a black rule.

     Content that is composed of repeated alternations of
     minor heading and text - such as the contributor section,
     the figures section, and the references section - is
     displayed as a two-column table, with the title/heading/label
     in the left column and the substance in the right column.

     Generated text is displayed in gray, to distinguish it
     from text derived from the source XML.

-->

<!-- ============================================================= -->
<!--  D4) CONSTRAINTS ON THE INPUT                                 -->
<!-- ============================================================= -->

<!--

1. The present transform doesn't handle:
     - sub-article or response
     - a full-featured narrative in supplementary-material
     - the attributes and elements pertaining to -groups-
       of figures or tables (fig-group, table-wrap-group).
       Their contained fig/table-wrap -are- handled.
     - col, colgroup

2. Article-meta that is not displayed at the top or end
   of the article:

                volume-id
                issue-id
                issue-title
                supplement
                page-range
                conference/conf-num
                conference/conf-sponsor
                conference/conf-theme

3. xlink attributes are suppressed *except for* xlink:href,
   which becomes an href or src attribute as follows:

      a) For inline-graphic, graphic, media:

           <img src="..."> & apply-templates

      b) For phrase-level elements

          <a href="..."> & apply-templates

      c) For block containers and grouping elements:

          <a href="..."> around whatever is being displayed
          as the object identifier, e.g.,

           - label or caption (for a graphic),
           - title (for a bio),
          or, if none such is available,
           - around the generated string "[link]"

4. Attributes and child elements displayed for graphic:

    The id and xlink:href attributes are displayed.
    The label, caption, and alt-text child elements are displayed.

5. Location of media files

   Transform assumes the @xlink:href value is an absolute
   path, not a relative one. To change this assumption:

   a) In the transform, create a variable which records
      the location of the graphics, e.g.,

      <xsl:variable name="graphics-dir"
                    select="'file:///c:/books/mybook/pix'"/>

   b) In the XML, use relative paths:

      <graphic xlink:href="poodle.jpg"/>

   c) Edit the appropriate template(s) in the transform
      to combine these two values:

      <img src="{concat($graphics-dir}, '/', {@xlink:href})"/>

5. Supplementary-material

   Transform assumes that the purpose & scope
   when tagging supplementary-material are:

     - point to an external file, such as a PDF or map
     - perhaps providing a paragraph or two of description
     - not using any of the much-manipulated elements,
       i.e., footnotes, tables, figures, and references.
-->


<!-- ============================================================= -->
<!--  1. TRANSFORM ELEMENT AND TOP-LEVEL SETTINGS                  -->
<!-- ============================================================= -->

<xsl:transform version="1.0" id="ViewNLM-v2-04.xsl" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:util="http://dtd.nlm.nih.gov/xsl/util"
	xmlns:mml="http://www.w3.org/1998/Math/MathML" exclude-result-prefixes="util xsl xlink mml">

	<xsl:output method="html"
		indent="yes"
		encoding="UTF-8"
		omit-xml-declaration="yes"
    		standalone="no"/>

	<xsl:strip-space elements="*"/>

	<xsl:preserve-space elements="preformat"/>


	<!--  Run-time parameters -->

	<!--  This stylesheet accepts no run-time parameters.              -->


	<!-- Keys -->

	<!-- To reduce dependency on a DTD for processing, we declare
     a key to use in lieu of the id() function. -->
	<xsl:key name="element-by-id" match="*[@id]" use="@id"/>

	<!-- Conversely, we can retrieve referencing elements
     from the node they reference. -->
	<xsl:key name="element-by-rid" match="*[@rid]" use="@rid"/>


	<!-- Lookup table for person-type strings
     used in nlm-citations -->
	<xsl:variable name="person-strings" select="document('')/*/util:map[@id='person-strings']/item"/>

	<util:map id="person-strings">
		<item source="editor" singular="editor" plural="editors"/>
		<item source="assignee" singular="assignee" plural="assignees"/>
		<item source="translator" singular="translator" plural="translators"/>
		<item source="transed" singular="translator and editor" plural="translators and editors"/>
		<item source="guest-editor" singular="guest editor" plural="guest editors"/>
		<item source="compiler" singular="compiler" plural="compilers"/>
		<item source="inventor" singular="inventor" plural="inventors"/>
		<!-- value 'allauthors' puts no string out -->
	</util:map>


	<!-- ============================================================= -->
	<!--  2. ROOT TEMPLATE - HANDLES HTML FRAMEWORK                    -->
	<!-- ============================================================= -->

	<xsl:template match="/">

		<xsl:call-template name="nl-1"/>
		<xsl:apply-templates/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  3. DOCUMENT ELEMENT                                          -->
	<!-- ============================================================= -->

	<!-- Can add sub-article and response to this match:
      - "make-a-piece" as required;
      - adapt the selection of elements that get managed as a set:
        footnotes, cross-references, tables, and figures. -->

	<xsl:template match="article">
		<xsl:call-template name="make-a-piece"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  3. "make-a-piece"                                            -->
	<!-- ============================================================= -->

	<!--  Generalized management of front, body, back, and trailing
      content, presently oeprates for sub-article and response
      exactly as for article. -->

	<!--  Organization of output:
         make-front
         make-body
         make-back
         make-figs-and-tables
         make-end-metadata
         ...then...
         do the same for any contained sub-article/response
-->

	<!-- initial context node is article -->

	<xsl:template name="make-a-piece">

		<!-- variable to be used in div id's to keep them unique -->
		<xsl:variable name="which-piece">
			<xsl:value-of select="concat(local-name(), '-level-', count(ancestor::*))"/>
		</xsl:variable>


		<!-- front matter, in table -->
		<xsl:call-template name="nl-2"/>
		<div id="{$which-piece}-front" class="fm">
			<!-- class is repeated on contained table elements -->
			<xsl:call-template name="nl-1"/>
			<xsl:call-template name="make-front"/>
			<xsl:call-template name="nl-1"/>
		</div>

		<!-- body -->
		<xsl:call-template name="nl-2"/>
		<div id="{$which-piece}-body" class="body">
			<xsl:call-template name="nl-1"/>
			<xsl:call-template name="make-body"/>
			<xsl:call-template name="nl-1"/>
		</div>

		<xsl:call-template name="nl-2"/>
		<div id="{$which-piece}-back" class="bm">
			<!-- class is repeated on contained table elements -->
			<xsl:call-template name="nl-1"/>
			<xsl:call-template name="make-back"/>
			<xsl:call-template name="nl-1"/>
		</div>

		<!-- retrieval metadata, at end -->
		<xsl:call-template name="nl-2"/>
		<div id="{$which-piece}-end-metadata" class="fm">
			<!-- class is repeated on contained table element -->
			<xsl:call-template name="nl-1"/>
			<xsl:call-template name="make-end-metadata"/>
			<xsl:call-template name="nl-1"/>
		</div>

		<!-- sub-article or response: calls this very template -->
		<!-- change context node -->
		<!--
  <xsl:for-each select="sub-article | response">
    <xsl:call-template name="make-a-piece"/>
  </xsl:for-each>

  <hr class="part-rule"/>
  <xsl:call-template name="nl-1"/>
  -->

	</xsl:template>


	<!-- ============================================================= -->
	<!-- 4. UTILITIES                                                  -->
	<!-- ============================================================= -->



	<!-- ============================================================= -->
	<!--  "capitalize" Capitalize a string                             -->
	<!-- ============================================================= -->

	<xsl:template name="capitalize">
		<xsl:param name="str"/>
		<xsl:value-of
			select="translate($str,
                          'abcdefghjiklmnopqrstuvwxyz',
                          'ABCDEFGHJIKLMNOPQRSTUVWXYZ')"
		/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  "language"                                                   -->
	<!-- ============================================================= -->

	<xsl:template name="language">
		<xsl:param name="lang"/>
		<xsl:choose>
			<xsl:when test="$lang='fr' or $lang='FR'"> (Fre).</xsl:when>
			<xsl:when test="$lang='jp' or $lang='JP'"> (Jpn).</xsl:when>
			<xsl:when test="$lang='ru' or $lang='RU'"> (Rus).</xsl:when>
			<xsl:when test="$lang='de' or $lang='DE'"> (Ger).</xsl:when>
			<xsl:when test="$lang='se' or $lang='SE'"> (Swe).</xsl:when>
			<xsl:when test="$lang='it' or $lang='IT'"> (Ita).</xsl:when>
			<xsl:when test="$lang='he' or $lang='HE'"> (Heb).</xsl:when>
			<xsl:when test="$lang='sp' or $lang='SP'"> (Spa).</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  "cleantitle"                                                 -->
	<!-- ============================================================= -->

	<xsl:template name="cleantitle">
		<xsl:param name="str"/>
		<xsl:value-of select="translate($str,'. ,-_','')"/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  "newlines"                                                   -->
	<!-- ============================================================= -->

	<!-- produces newlines in output, to increase legibility of XML    -->

	<xsl:template name="nl-1">
		<xsl:text>&#xA;</xsl:text>
	</xsl:template>

	<xsl:template name="nl-2">
		<xsl:text>&#xA;</xsl:text>
		<xsl:text>&#xA;</xsl:text>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  make-id, make-src, make-href, make-email, make-anchor                     -->
	<!-- ============================================================= -->

	<xsl:template name="make-id">
		<xsl:if test="@id">
			<xsl:attribute name="id">
				<xsl:value-of select="@id"/>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<xsl:template name="make-src">
		<xsl:if test="@xlink:href">
			<xsl:attribute name="src">
				<xsl:value-of select="@xlink:href"/>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<xsl:template name="make-href">
		<xsl:if test="@xlink:href">
			<xsl:attribute name="href">
				<xsl:value-of select="@xlink:href"/>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

	<xsl:template match="email">
		<a>
			<xsl:attribute name="href">
				<xsl:value-of select="concat('mailto:', .)"/>
			</xsl:attribute>
			<xsl:value-of select="."/>
		</a>
	</xsl:template>

	<xsl:template name="make-email">
		<xsl:if test="@xlink:href">
			<xsl:attribute name="href">
				<xsl:value-of select="concat('mailto:', @xlink:href)"/>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="make-anchor">
		<xsl:if test="@id">
			<a name="{@id}"/>
		</xsl:if>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  display-id                                                   -->
	<!-- ============================================================= -->

	<xsl:template name="display-id">

		<xsl:variable name="display-phrase">
			<xsl:choose>
				<xsl:when test="self::disp-formula">
					<xsl:text>Formula ID</xsl:text>
				</xsl:when>
				<xsl:when test="self::chem-struct-wrapper">
					<xsl:text>Chemical Structure Wrapper ID</xsl:text>
				</xsl:when>
				<xsl:when test="self::chem-struct">
					<xsl:text>Chemical Structure ID</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>ID</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="@id">
			<span class="gen">
				<xsl:text>[</xsl:text>
				<xsl:value-of select="$display-phrase"/>
				<xsl:text>: </xsl:text>
			</span>
			<xsl:value-of select="@id"/>
			<span class="gen">
				<xsl:text>]</xsl:text>
			</span>
		</xsl:if>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  "table-setup": left column wide or narrow                    -->
	<!-- ============================================================= -->

	<xsl:template name="table-setup-l-wide">
		<xsl:call-template name="nl-1"/>
		<tr>
			<td width="30%"/>
			<td/>
		</tr>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template name="table-setup-l-narrow">
		<xsl:call-template name="nl-1"/>
		<tr>
			<td width="10%"/>
			<td/>
		</tr>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template name="table-setup-even">
		<xsl:call-template name="nl-1"/>
		<tr>
			<td width="50%"/>
			<td/>
		</tr>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!-- "make-figs-and-tables"                                        -->
	<!-- ============================================================= -->

	<!-- initial context node is article -->

	<xsl:template name="make-figs-and-tables">

		<xsl:if test="body//fig[not(parent::fig-group)] | back//fig[not(parent::fig-group)]">
			<xsl:call-template name="nl-1"/>

			<div class="Title1">Figures</div>
			<xsl:call-template name="nl-1"/>

				<xsl:apply-templates select="body//fig | back//fig" mode="put-at-end"/>
		</xsl:if>

		<xsl:if test="body//table-wrap | back//table-wrap">
			<xsl:call-template name="nl-1"/>

			<div class="Title1">Tables</div>
			<xsl:call-template name="nl-1"/>

			<xsl:apply-templates select="body//table-wrap | back//table-wrap" mode="put-at-end"/>
			<xsl:call-template name="nl-1"/>
		</xsl:if>

	</xsl:template>



	<!-- ============================================================= -->
	<!-- 6. SUPPRESSED ELEMENTS                                        -->
	<!-- ============================================================= -->

	<!-- suppressed in no-mode (processed in mode "front") -->

	<xsl:template match="journal-meta | article-meta"/>


	<!-- not handled by this transform -->

	<xsl:template match="sub-article | response"/>


	<!-- xlink attributes are generally suppressed; note however that
     @xlink:href is used in some element templates. -->

	<xsl:template match="@xlink:*"/>


	<!-- Tables and figures are displayed at the end of the document,
     using mode "put-at-end".
     So, in no-mode, we suppress them: -->

	<xsl:template match="fig | fig-group | table-wrap | table-wrap-group"/>



	<!-- ============================================================= -->
	<!-- CALLED TEMPLATES FOR ARTICLE PARTS                            -->
	<!-- ============================================================= -->

	<!-- ============================================================= -->
	<!--  7. MAKE-HTML-HEADER                                          -->
	<!-- ============================================================= -->

	<xsl:template name="make-html-header">

		<head>
			<xsl:call-template name="nl-1"/>
			<title>
				<xsl:choose>

					<xsl:when test="/article/front/journal-meta/journal-id
                        [@journal-id-type='pubmed']">
						<xsl:value-of
							select="/article/front/journal-meta/journal-id
                                [@journal-id-type='pubmed']"/>
						<xsl:text>: </xsl:text>
					</xsl:when>

					<xsl:when test="/article/front/journal-meta/journal-id
                       [@journal-id-type='publisher']">
						<xsl:value-of
							select="/article/front/journal-meta/journal-id
                                [@journal-id-type='publisher']"/>
						<xsl:text>: </xsl:text>
					</xsl:when>

					<xsl:when test="/article/front/journal-meta/journal-id">
						<xsl:value-of
							select="/article/front/journal-meta/journal-id
                                [1][@journal-id-type]"/>
						<xsl:text>: </xsl:text>
					</xsl:when>

					<xsl:otherwise/>
				</xsl:choose>

				<xsl:for-each select="/article/front/article-meta/volume">
					<xsl:text>Vol. </xsl:text>
					<xsl:apply-templates/>
					<xsl:text> </xsl:text>
				</xsl:for-each>

				<xsl:for-each select="/article/front/article-meta/issue">
					<xsl:text>Issue </xsl:text>
					<xsl:apply-templates/>
					<xsl:text>: </xsl:text>
				</xsl:for-each>

				<xsl:if test="/article/front/article-meta/fpage">
					<xsl:choose>
						<xsl:when test="../lpage">
							<xsl:text>pp. </xsl:text>
							<xsl:value-of select="/article/front/article-meta/fpage"/>
							<xsl:text>–</xsl:text>
							<xsl:value-of select="/article/front/article-meta/lpage"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>p. </xsl:text>
							<xsl:value-of select="/article/front/article-meta/fpage"/>
							<xsl:text> </xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>

			</title>
			<xsl:call-template name="nl-1"/>
			<link rel="stylesheet" href="http://www.openmedicine.ca/styles/common.css" type="text/css" />
			<link rel="stylesheet" title="Medium" href="http://www.openmedicine.ca/styles/medium.css" type="text/css" />
			<link rel="stylesheet" href="http://www.openmedicine.ca/styles/articleView.css" type="text/css" />
			<link rel="stylesheet" href="http://www.openmedicine.ca/styles/header-default.css" type="text/css" />
			<xsl:call-template name="nl-1"/>
		</head>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  8. MAKE-FRONT                                                -->
	<!-- ============================================================= -->

	<!-- initial context node is /article -->

	<xsl:template name="make-front">

		<xsl:call-template name="nl-1"/>

		<!-- Titles and author group -->
		<!-- All data comes from front/article-meta -->
			<!-- change context to front/article-meta (again) -->
			<xsl:for-each select="front/article-meta">
				<xsl:apply-templates select="article-categories"/>
				<xsl:apply-templates select="title-group" mode="front"/>
				<xsl:call-template name="nl-1"/>

				<!-- each contrib makes a row: name at left, details at right -->
			<div id="Byline">
				<xsl:for-each select="contrib-group/contrib">
							<xsl:choose>
								<xsl:when test="@xlink:href">
									<a>
										<xsl:call-template name="make-href"/>
										<xsl:call-template name="make-id"/>
										<xsl:apply-templates select="name | collab" mode="front"/>
									</a>
								</xsl:when>
								<xsl:otherwise>
										<xsl:apply-templates select="name | collab" mode="front"/>
										<xsl:variable name="nodetotal" select="count(../*)"/>
										<xsl:variable name="position" select="position()"/>
										<xsl:choose>
											<xsl:when test="$nodetotal=$position"/>
											<xsl:when test="following-sibling::contrib[1]/collab">
												<xsl:text>; </xsl:text>
											</xsl:when>
											<xsl:otherwise>, </xsl:otherwise>
										</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>

							<!-- the name element handles any contrib/xref and contrib/degrees -->
							<xsl:apply-templates
								select="*[not(self::name) and not(self::collab) and not(self::xref) and not(self::degrees) and not(self::bio)]" mode="front"/>

							<xsl:call-template name="nl-1"/>

					<xsl:call-template name="nl-1"/>
				</xsl:for-each>
			</div>

				<!-- end of contrib -->

				<!-- abstract(s) -->

				<xsl:if test="abstract | trans-abstract and not(contains(/article/front/article-meta/article-categories/subj-group/subject, 'Analysis and Comment')) and not(contains(/article/front/article-meta/article-categories/subj-group/subject, 'Editorial')) and not(contains(/article/front/article-meta/article-categories/subj-group/subject, 'Clinical Practice')) and not(contains(/article/front/article-meta/article-categories/subj-group/subject, 'Case Report'))">

					<xsl:for-each select="abstract | trans-abstract">
						<div class="Title1 Abstract">ABSTRACT</div>
						<xsl:apply-templates select="*[not(self::title)]"/>
						<xsl:call-template name="nl-1"/>
					</xsl:for-each>
					<!-- end of abstract or trans-abstract -->

				</xsl:if>
				<!-- end of dealing with abstracts -->

			<div id="authors">
				<xsl:for-each select="contrib-group/contrib">
					<xsl:apply-templates
						select="bio" mode="front"/>
					<xsl:text> </xsl:text>
				</xsl:for-each>
			</div>

<!-- Insert Competing Interests -->
<xsl:for-each select="/article/back/fn-group/fn[@fn-type='conflict']">
	<div id="CompetingInterests">
		<xsl:call-template name="make-id"/>
		<span class="subTitle2">Competing interests: </span>
		<xsl:choose>
			<xsl:when test="contains(., 'Competing interests: ')">
				<xsl:value-of select="substring-after(.,': ')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</div>
</xsl:for-each>

<!-- Insert Contributors -->
<xsl:for-each select="/article/back/fn-group/fn[@fn-type='con']">
	<div id="Funding">
		<xsl:call-template name="make-id"/>
		<span class="subTitle2">Contributors: </span>
		<xsl:choose>
			<xsl:when test="contains(., 'Contributors: ')">
				<xsl:value-of select="substring-after(.,': ')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</div>
	<br/>
</xsl:for-each>

<!-- Insert Funding -->
<xsl:for-each select="/article/back/fn-group/fn[@fn-type='financial-disclosure']">
	<div id="Funding">
		<xsl:call-template name="make-id"/>
		<span class="subTitle2">Funding: </span>
		<xsl:choose>
			<xsl:when test="contains(., 'Funding source: ')">
				<xsl:value-of select="substring-after(.,': ')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</div>
</xsl:for-each>

				<!-- author notes -->
				<xsl:if test="author-notes">
					<xsl:apply-templates select="author-notes" mode="front"/>
				</xsl:if>

				<!-- Related articles -->
				<xsl:if test="related-article">
					<hr class="part-rule"/>
					<table width="100%" class="fm">
						<xsl:call-template name="table-setup-l-wide"/>
						<xsl:call-template name="nl-1"/>
						<tr>
							<xsl:call-template name="nl-1"/>
							<td colspan="2" valign="top">
								<!-- related article -->
								<xsl:apply-templates select="related-article"/>
							</td>
							<xsl:call-template name="nl-1"/>
						</tr>
						<xsl:call-template name="nl-1"/>
					</table>
					<xsl:call-template name="nl-1"/>
				</xsl:if>


				<!-- end of the titles-and-authors context; return to previous context -->
			</xsl:for-each>





<!-- Insert other bits of Author info -->

		<xsl:call-template name="nl-2"/>

		<!-- end of big front-matter pull -->
	</xsl:template>


	<!-- ============================================================= -->
	<!--  9. MAKE-BODY                                                 -->
	<!-- ============================================================= -->

	<!-- initial context node is article -->

	<xsl:template name="make-body">
		<!-- change context node -->
		<xsl:for-each select="body">
			<xsl:call-template name="nl-1"/>

			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</xsl:for-each>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  10. MAKE-BACK                                                -->
	<!-- ============================================================= -->

	<!-- initial context node is article -->

	<xsl:template name="make-back">

		<xsl:call-template name="nl-1"/>

		<!-- change context node to back -->
		<xsl:for-each select="back">
			<xsl:apply-templates select="title"/>
			<xsl:if test=".//fn-group[(self::fn-group/fn[@fn-type='other'])]">
				<div class="Title1">Footnotes</div>
				<xsl:apply-templates select=".//fn-group/fn[@fn-type='other']"/>
				<xsl:call-template name="nl-1"/>
			</xsl:if>

			<xsl:apply-templates select="*[not(self::title) and not(self::fn-group)]"/>
			<xsl:call-template name="nl-1"/>
		</xsl:for-each>

	</xsl:template>



	<!-- ============================================================= -->
	<!--  11. MAKE-POST-PUBLICATION                                    -->
	<!-- ============================================================= -->


	<!-- At present the transform does not support
     subarticles and responses. To include that
     support in the present structure, fill out
     this template, call the "make-a-piece"
     template to ensure the details are handled
     in the same way and by the same templates
     as for the main article body. -->



	<!-- ============================================================= -->
	<!--  12. MAKE-END-METADATA                                        -->
	<!-- ============================================================= -->

	<!-- This metadata is displayed after the back and figs-and-tables
     because (when it it exists) it will be too long to display
     with the other metadata that is displayed before the body.    -->

	<!-- It is metadata for retrieval: categories, keywords, etc.      -->

	<!-- The context node when this template is called is the article
     or, when supported, the sub-article or response.              -->

	<xsl:template name="make-end-metadata">

		<!-- change context node -->
		<xsl:for-each select="front/article-meta">

			<xsl:if
				test="kwd-group
                | conference">

				<hr class="part-rule"/>

				<table width="100%" class="fm">

					<xsl:call-template name="table-setup-l-wide"/>

					<xsl:call-template name="nl-1"/>
					<tr>

						<xsl:call-template name="nl-1"/>
						<td colspan="2" valign="top">

							<!-- keyword group -->
							<xsl:apply-templates select="kwd-group"/>

							<!-- conference information -->
							<xsl:apply-templates select="conference"/>

						</td>
						<xsl:call-template name="nl-1"/>

					</tr>
					<xsl:call-template name="nl-1"/>

				</table>
				<xsl:call-template name="nl-1"/>

			</xsl:if>
		</xsl:for-each>

	</xsl:template>



	<!-- ============================================================= -->
	<!--  Article Categories                                           -->
	<!-- ============================================================= -->

	<xsl:template match="article-categories">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="subj-group">
		<xsl:if test="not(parent::subj-group)">
			<div id="Category">
				<xsl:apply-templates/>
			</div>
		</xsl:if>
	</xsl:template>

	<xsl:template match="subject">
			<xsl:apply-templates/>
	</xsl:template>

	<!-- There may be many series-title elements; there
     may be one series-text (description) element. -->
	<xsl:template match="series-title">
		<xsl:if test="not(preceding-sibling::series-title)">
			<span class="gen">
				<xsl:text>Series: </xsl:text>
			</span>
		</xsl:if>
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
		<xsl:if test="not(following-sibling::*)">
			<br/>
		</xsl:if>
	</xsl:template>

	<xsl:template match="series-text">
		<xsl:apply-templates/>
		<br/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  Keywords                                                     -->
	<!-- ============================================================= -->

	<!-- kwd-group and its kwd occur only in article-meta -->
	<xsl:template match="kwd-group">
		<span class="gen">
			<xsl:call-template name="make-id"/>
			<xsl:text>Keywords: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
	</xsl:template>

	<xsl:template match="kwd">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</span>

		<xsl:call-template name="make-keyword-punct"/>
	</xsl:template>

	<xsl:template name="make-keyword-punct">

		<xsl:choose>
			<xsl:when test="following-sibling::kwd">
				<xsl:text>, </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>.</xsl:text>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  Related article                                              -->
	<!-- ============================================================= -->

	<xsl:template match="related-article">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<span class="gen">
						<xsl:text>Related Article: </xsl:text>
					</span>
					<xsl:apply-templates mode="none"/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">
					<xsl:call-template name="make-id"/>
					<xsl:text>Related Article: </xsl:text>
				</span>
				<xsl:apply-templates mode="none"/>
			</xsl:otherwise>
		</xsl:choose>
		<br/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  Conference                                                   -->
	<!-- ============================================================= -->

	<xsl:template match="conference">
		<span class="gen">
			<xsl:text>Conference: </xsl:text>
		</span>
		<xsl:call-template name="make-conference"/>
		<br/>
	</xsl:template>

	<!-- doesn't use conf-num, conf-sponsor, conf-theme -->
	<xsl:template name="make-conference">

		<xsl:apply-templates select="conf-acronym" mode="add-period"/>
		<xsl:apply-templates select="conf-name" mode="add-period"/>
		<xsl:apply-templates select="conf-loc" mode="add-period"/>
		<xsl:apply-templates select="conf-date" mode="add-period"/>

	</xsl:template>

	<xsl:template match="*" mode="add-period">
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  NARRATIVE CONTENT AND COMMON STRUCTURES                      -->
	<!-- ============================================================= -->

	<!-- ============================================================= -->
	<!--  13. PARAGRAPH WITH ITS SUBTLETIES                            -->
	<!-- ============================================================= -->


	<!-- Make the different gradiations of subtitles -->

	<xsl:template match="body/sec/sec/p">
		<xsl:choose>
			<xsl:when test="local-name(preceding-sibling::node()[1])='title'">
				<p>
					<span class="subTitle1">
						<xsl:value-of select="../title"/>
						<xsl:text>. </xsl:text>
					</span>
					<xsl:call-template name="nl-1"/>
					<xsl:apply-templates/>
				</p>
			</xsl:when>
			<xsl:otherwise>
				<p>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</p>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="body/sec/sec/sec/p">
		<xsl:choose>
			<xsl:when test="local-name(preceding-sibling::node()[1])='title'">
				<p>
					<em>
						<xsl:value-of select="../title"/>
					</em>
					<xsl:text>. </xsl:text>
					<xsl:apply-templates/>
				</p>
			</xsl:when>
			<xsl:otherwise>
				<p>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</p>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="body/sec/sec/sec/sec/p">
		<xsl:choose>
			<xsl:when test="local-name(preceding-sibling::node()[1])='title'">
				<p>
					<xsl:value-of select="../title"/>
					<xsl:text>. </xsl:text>
					<xsl:apply-templates/>
				</p>
			</xsl:when>
			<xsl:otherwise>
				<p>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</p>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="p">
		<xsl:choose>
			<xsl:when test="parent::list-item">
				<xsl:apply-templates/>
				<xsl:if test="following-sibling::p">
					<br/>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<p>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</p>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- The first p in a footnote displays the fn symbol or,
     if no symbol, the fn ID -->
	<xsl:template match="fn/p[1]">
		<p>
			<xsl:call-template name="make-id"/>
			<xsl:choose>
				<xsl:when test="preceding-sibling::label">
					<span class="fn-label">
						<xsl:value-of select="preceding-sibling::label"/>
					</span>
				</xsl:when>
				<xsl:when test="../@symbol">
					<span class="fn-label">
						<xsl:value-of select="../@symbol"/>
					</span>
				</xsl:when>
				<xsl:when test="../@label">
					<span class="fn-label">
						<xsl:value-of select="../@label"/>
					</span>
				</xsl:when>
				<xsl:when test="../@id">
					<span class="fn-label">
						<xsl:value-of select="../@id"/>
					</span>
				</xsl:when>
			</xsl:choose>
			<xsl:apply-templates/>
		</p>
	</xsl:template>

	<xsl:template match="speech/p[1]">
		<p>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates select="preceding-sibling::speaker" mode="show-it-here"/>
			<xsl:text> </xsl:text>
			<xsl:apply-templates/>
		</p>
	</xsl:template>

	<!-- prevent the first def/p from causing a p tag
     which would display an unwanted break -->
	<xsl:template match="def/p[1]">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</span>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  14. SECTION                                                  -->
	<!-- ============================================================= -->

	<!-- the first body/sec puts out no rule at its top,
     because body already puts out a part-rule at its top;
     subsequent body/secs do put out a section-rule -->
	<xsl:template match="body/sec">
		<xsl:call-template name="nl-1"/>

		<div>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- no other level of sec puts out a rule -->
	<xsl:template match="sec">
		<xsl:choose>
			<xsl:when test="@sec-type='display-objects'"/>
			<xsl:otherwise>
				<div>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</div>
				<xsl:call-template name="nl-1"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  15. LIST and its Internals                                   -->
	<!-- ============================================================= -->

	<xsl:template match="list">
		<xsl:call-template name="nl-1"/>
		<xsl:call-template name="make-anchor"/>
		<xsl:if test="label | title">
			<xsl:apply-templates select="label | title"/>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="@list-type='simple' or list-item/label">
				<ul style="list-style-type: none;">
					<xsl:call-template name="nl-1"/>
					<xsl:apply-templates select="list-item"/>
					<xsl:call-template name="nl-1"/>
				</ul>
			</xsl:when>
			<xsl:when test="@list-type='bullet'">
				<xsl:call-template name="nl-1"/>
				<ul>
					<xsl:call-template name="nl-1"/>
					<xsl:apply-templates select="list-item"/>
					<xsl:call-template name="nl-1"/>
				</ul>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="nl-1"/>
				<ol>
					<xsl:call-template name="nl-1"/>
					<xsl:apply-templates select="list-item"/>
					<xsl:call-template name="nl-1"/>
				</ol>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="list-item">
		<xsl:call-template name="nl-1"/>
		<li>
			<xsl:apply-templates/>
		</li>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  16. DISPLAY-QUOTE                                            -->
	<!-- ============================================================= -->

	<xsl:template match="disp-quote">
		<xsl:call-template name="nl-1"/>
		<blockquote>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</blockquote>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  17. SPEECH and its internals                                 -->
	<!-- ============================================================= -->

	<!-- first p will pull in the speaker
     in mode "show-it-here" -->
	<xsl:template match="speech">
		<blockquote>
			<xsl:call-template name="make-id"/>
			<xsl:call-template name="nl-1"/>
			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</blockquote>
	</xsl:template>

	<xsl:template match="speaker" mode="show-it-here">
		<b>
			<xsl:apply-templates/>
		</b>
	</xsl:template>

	<!-- in no mode -->
	<xsl:template match="speaker"/>


	<!-- ============================================================= -->
	<!--  18. STATEMENT and its internals                              -->
	<!-- ============================================================= -->

	<xsl:template match="statement">
		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:call-template name="nl-1"/>
			<xsl:apply-templates/>
		</div>
	</xsl:template>

	<xsl:template match="statement/label | statement/title">
		<xsl:call-template name="nl-1"/>
		<p>
			<b>
				<xsl:apply-templates/>
			</b>
		</p>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  19. VERSE-GROUP and its internals                            -->
	<!-- ============================================================= -->

	<xsl:template match="verse-group">
		<xsl:call-template name="nl-1"/>
		<blockquote>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</blockquote>
	</xsl:template>

	<xsl:template match="verse-line">
		<xsl:call-template name="nl-1"/>
		<xsl:apply-templates/>
		<br/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  20. BOXED-TEXT                                               -->
	<!-- ============================================================= -->

	<xsl:template match="boxed-text">

		<xsl:call-template name="nl-1"/>

		<div class="boxed-text">
			<xsl:apply-templates/>
		</div>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  21. PREFORMAT                                                -->
	<!-- ============================================================= -->


	<xsl:template match="preformat" name="format-as-line-for-line">
		<pre><xsl:call-template name="make-id"/><xsl:apply-templates/></pre>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  22. SUPPLEMENTARY MATERIAL                                   -->
	<!-- ============================================================= -->

	<xsl:template match="supplementary-material">
		<xsl:apply-templates select="*[not(self::label)]"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  23. DISPLAY FORMULA, CHEM-STRUCT-WRAPPER                     -->
	<!-- ============================================================= -->

	<!-- both are grouping elements to keep parts together -->
	<xsl:template match="disp-formula | chem-struct-wrapper">
		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:call-template name="display-id"/>
			<br/>
			<xsl:apply-templates/>
		</div>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  24. FORMATTING ELEMENTS                                      -->
	<!-- ============================================================= -->
	<xsl:template match="blockquote">
		<blockquote>
			<xsl:apply-templates/>
		</blockquote>
	</xsl:template>

	<xsl:template match="bold">
		<b>
			<xsl:apply-templates/>
		</b>
	</xsl:template>

	<xsl:template match="italic">
		<i>
			<xsl:apply-templates/>
		</i>
	</xsl:template>

	<xsl:template match="monospace">
		<span class="monospace">
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<xsl:template match="overline">
		<span class="overline">
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<xsl:template match="sc">

		<!-- handle any tags as usual, until
       we're down to the text strings -->
		<small>
			<xsl:apply-templates/>
		</small>
	</xsl:template>

	<xsl:template match="sc//text()">
		<xsl:param name="str" select="."/>

		<xsl:call-template name="capitalize">
			<xsl:with-param name="str" select="$str"/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="strike">
		<s>
			<xsl:apply-templates/>
		</s>
	</xsl:template>

	<xsl:template match="sub">
		<sub>
			<xsl:apply-templates/>
		</sub>
	</xsl:template>

	<xsl:template match="sup">
		<sup>
			<xsl:apply-templates/>
		</sup>
	</xsl:template>

	<xsl:template match="underline">
		<u>
			<xsl:apply-templates/>
		</u>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  25. SEMANTIC ELEMENTS                                        -->
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
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="inline-graphic">
		<xsl:call-template name="nl-1"/>
		<img>
			<xsl:call-template name="make-src"/>
			<xsl:call-template name="make-id"/>
		</img>
	</xsl:template>

	<xsl:template match="inline-formula">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<!-- is meant be a link: we assume the xlink:href
     attribute is used, although it is not
     required by the DTD. -->
	<xsl:template match="inline-supplementary-material">
		<xsl:call-template name="nl-1"/>
		<a>
			<xsl:call-template name="make-href"/>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</a>
	</xsl:template>

	<xsl:template match="glyph-data">
		<xsl:call-template name="nl-1"/>
		<span class="take-note">
			<xsl:call-template name="make-id"/>
			<xsl:text>[glyph data here: ID=</xsl:text>
			<xsl:value-of select="@id"/>
			<xsl:text>]</xsl:text>
		</span>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  Named Content                                                -->
	<!-- ============================================================= -->

	<xsl:template match="named-content">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<span class="gen">
						<xsl:text>[</xsl:text>
						<xsl:value-of select="@content-type"/>
						<xsl:apply-templates/>
						<xsl:text>]</xsl:text>
					</span>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<span class="gen">
						<xsl:text>[</xsl:text>
						<xsl:value-of select="@content-type"/>
						<xsl:text>: </xsl:text>
					</span>
					<xsl:apply-templates/>
					<span class="gen">
						<xsl:text>]</xsl:text>
					</span>
				</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  26. BREAK AND HORIZONTAL RULE                                -->
	<!-- ============================================================= -->

	<xsl:template match="break" name="make-break">
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="hr" name="make-rule">
		<xsl:call-template name="nl-1"/>
		<hr/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>



	<!-- ============================================================= -->
	<!--  27. CHEM-STRUCT                                              -->
	<!-- ============================================================= -->

	<xsl:template match="chem-struct">

		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:call-template name="display-id"/>

			<xsl:choose>
				<xsl:when test="@xlink:href">
					<a>
						<xsl:call-template name="make-href"/>
						<xsl:apply-templates/>
					</a>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates/>
				</xsl:otherwise>
			</xsl:choose>

		</span>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  28. TEX-MATH and MML:MATH                                    -->
	<!-- ============================================================= -->

	<xsl:template match="tex-math">
		<span class="take-note">
			<xsl:text>[</xsl:text>
			<xsl:copy-of select="."/>
			<xsl:text>]</xsl:text>
		</span>
	</xsl:template>


	<!-- can presume this is meant to be inline -->
	<xsl:template match="inline-formula//mml:math">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- we don't -know- mml:math in general to be inline,
     so treat it as block.
     Put it in a table to get a pretty border. -->
	<xsl:template match="mml:math">

		<xsl:choose>

			<xsl:when test="@xlink:href">
				<table border="1">
					<tr>
						<td valign="top">
							<a>
								<xsl:call-template name="make-href"/>
								<xsl:call-template name="make-id"/>
								<xsl:apply-templates/>
							</a>
						</td>
					</tr>
				</table>
			</xsl:when>

			<xsl:otherwise>
				<table border="1">
					<tr>
						<td valign="top">
							<span>
								<xsl:call-template name="make-id"/>
								<xsl:apply-templates/>
							</span>
						</td>
					</tr>
				</table>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  29. GRAPHIC and MEDIA                                        -->
	<!-- ============================================================= -->

	<xsl:template match="graphic">
		<a>
			<xsl:call-template name="make-href"/>
			<xsl:call-template name="make-id"/>
			<xsl:text>view</xsl:text>
		</a>
		<xsl:call-template name="nl-1"/>
	</xsl:template>



	<xsl:template match="media">
		<xsl:text>&#xA;</xsl:text>
		<div class="figure">
		<xsl:call-template name="make-id"/>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@xlink:href}">

			<img src="http://openmedicine.ca/images/figure.png" alt="{@label}" />
		</a>
		<span class="subTitle1">
			<xsl:value-of select="label"/>
			<xsl:text>. </xsl:text>
		</span>
		<xsl:value-of select="caption"/>
		<xsl:text> [</xsl:text>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@xlink:href}">view</a>
		<xsl:text>]</xsl:text>
		<xsl:call-template name="nl-1"/>
		</div>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  30. ARRAY                                                    -->
	<!-- ============================================================= -->

	<xsl:template match="array">
		<hr width="40%" align="left" noshade="1"/>
		<xsl:call-template name="nl-1"/>

		<table>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</table>
		<xsl:call-template name="nl-1"/>

		<hr width="40%" align="left" noshade="1"/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  31. CAPTIONING                                               -->
	<!-- ============================================================= -->

	<!-- the chooses before and after the element content
     tweak the display as appropriate -->

	<xsl:template match="alt-text | attrib">

		<!-- element-specific handling before content: -->
		<xsl:choose>

			<!-- alt-text gets a generated label-->
			<xsl:when test="self::alt-text">
				<xsl:if test="not(ancestor::fig)
                  and not(ancestor::table)">
					<br/>
				</xsl:if>

				<span class="gen">
					<xsl:call-template name="make-id"/>
					<xsl:text>Alternate Text: </xsl:text>
				</span>
			</xsl:when>

			<!-- attrib is preceded by spaces plus em-dash -->
			<xsl:when test="self::attrib">
				<xsl:text>&#8194;&#8194;&#8212;</xsl:text>
			</xsl:when>
		</xsl:choose>

		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>

		<!-- element-specific handling after content: -->
		<xsl:choose>

			<!-- alt-text and long-desc get a break after -->
			<xsl:when test="self::alt-text | self::long-desc">
				<br/>
			</xsl:when>

		</xsl:choose>

	</xsl:template>
	
	<xsl:template match="label">
		<xsl:choose>
			<xsl:when test="parent::author-notes | parent::corresp">
				<span class="au-label">
					<xsl:apply-templates/>
				</span>
			</xsl:when>
			<xsl:when test="parent::fn">
				<!-- class fn-label = class au-label -->
				<span class="fn-label">
					<xsl:apply-templates/>
				</span>
			</xsl:when>
			<xsl:otherwise>
				<span class="label">
					<xsl:apply-templates/>&#xA0;
				</span>
			</xsl:otherwise>
		</xsl:choose>		
	</xsl:template>
	
	<!-- These are handled inside ref -->
	<xsl:template match="label" mode="none"/>
	<xsl:template match="label" mode="nscitation"/>

	<xsl:template match="caption">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</span>
		<br/>
	</xsl:template>


	<!-- mixed-content; used in figures, tables, etc. -->
	<xsl:template match="long-desc">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</span>
		<br/>
	</xsl:template>

	<xsl:template match="object-id">
		<xsl:choose>
			<xsl:when test="@pub-id-type">
				<xsl:value-of select="@pub-id-type"/>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">
					<xsl:text>Object ID</xsl:text>
				</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:text>: </xsl:text>
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
		<br/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  32. FIGURE, Normal MODE                                      -->
	<!-- ============================================================= -->

	<!-- each figure is a row -->
	<xsl:template match="fig">

		<!-- left column:  graphic
         right column: captioning elements - label, caption, etc. -->

<div class="figure">
	<xsl:call-template name="make-id"/>
	<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@id}.png">
		<img src="http://openmedicine.ca/images/figure.png" alt="{label}"/>
	</a>
	<span class="subTitle1">
		<xsl:value-of select="label"/>
		<xsl:text>. </xsl:text>
	</span>
		<xsl:value-of select="caption"/>
		<xsl:text> [</xsl:text>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@id}.png">view</a>
		<xsl:text>]</xsl:text>
	<xsl:call-template name="nl-1"/>
</div>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  33. TABLE-WRAP, MODE PUT-AT-END                              -->
	<!-- ============================================================= -->

	<xsl:template match="table-wrap" mode="put-at-end">
		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates select="@id"/>
			<xsl:apply-templates/>
			<br/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="table-wrap/@id">
		<span class="gen">
			<xsl:text>[TableWrap ID: </xsl:text>
		</span>
		<xsl:value-of select="."/>
		<span class="gen">
			<xsl:text>] </xsl:text>
		</span>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<xsl:template match="table">
		<table width="100%" class="bm">

			<xsl:if test="@frame">
				<xsl:attribute name="frame">
					<xsl:value-of select="@frame"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@rules">
				<xsl:attribute name="rules">
					<xsl:value-of select="@rules"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:call-template name="nl-1"/>

			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</table>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="thead">
		<thead>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</thead>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="th">
		<th>
			<xsl:call-template name="make-id"/>
			<xsl:for-each select="@*">
				<xsl:attribute name="{name(.)}">
					<xsl:value-of select="."/>
				</xsl:attribute>
			</xsl:for-each>
			<xsl:apply-templates/>
		</th>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="tr">
		<tr>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</tr>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="td">
		<td valign="top">
			<xsl:call-template name="make-id"/>
			<xsl:for-each select="@*">
				<xsl:attribute name="{name(.)}">
					<xsl:value-of select="."/>
				</xsl:attribute>
			</xsl:for-each>
			<xsl:apply-templates/>
		</td>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="tfoot | table-wrap-foot">
		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>



	<!-- ============================================================= -->
	<!-- MODE front                                                    -->
	<!-- ============================================================= -->

	<!--
<xsl:template match="journal-meta/journal-id
                   | journal-meta/journal-title
                   | journal-meta/journal-abbrev-title
                   | journal-meta/publisher"/>
-->

	<!-- ============================================================= -->
	<!--  34) JOURNAL-META (in order of appearance in output)          -->
	<!-- ============================================================= -->

	<!-- journal-id -->

	<xsl:template match="journal-id[@journal-id-type]" mode="front">
		<span class="gen">
			<xsl:text>Journal ID (</xsl:text>
		</span>
		<xsl:value-of select="@journal-id-type"/>
		<span class="gen">
			<xsl:text>): </xsl:text>
		</span>
		<xsl:value-of select="."/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- abbrev-journal-title -->

	<xsl:template match="abbrev-journal-title" mode="front">
		<span class="gen">
			<xsl:text>Journal Abbreviation: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- issn -->

	<xsl:template match="issn" mode="front">
		<span class="gen">
			<xsl:text>ISSN: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- publisher -->
	<!-- required name, optional location -->

	<xsl:template match="publisher" mode="front">
		<xsl:apply-templates mode="front"/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="publisher-name" mode="front">
		<span class="gen">
			<xsl:text>Publisher: </xsl:text>
		</span>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="publisher-loc" mode="front">
		<!-- if present, follows a publisher-name, so produces a comma -->
		<xsl:text>, </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<!-- notes -->

	<xsl:template match="notes" mode="front">
		<span class="gen">Notes: </span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  35) ARTICLE-META (in order of appearance in output)          -->
	<!-- ============================================================= -->

	<!-- ext-link -->

	<xsl:template match="ext-link" mode="front">
		<span class="gen">
			<xsl:call-template name="make-id"/>
			<xsl:text>Link: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- supplementary-material -->

	<!-- Begins with:
    Object Identifier <object-id>, zero or more
    Label (Of a Figure, Reference, Etc.) <label>, zero or one
    Caption of a Figure, Table, Etc. <caption>, zero or one
    Any combination of:
      All the accessibility elements:
        Alternate Title Text (For a Figure, Etc.) <alt-text>
        Long Description <long-desc>
      All the address linking elements:
        Email Address <email>
        External Link <ext-link>
        Uniform Resource Indicator (URI) <uri>

  Then an ordinary combination of para-level elements

  Ending with:
    Any combination of:
    Attribution <attrib>
    Copyright Statement <copyright-statement>
-->

	<xsl:template match="supplementary-material" mode="front">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- self-uri -->

	<xsl:template match="self-uri" mode="front">
		<span class="gen">
			<xsl:text>Self URI: </xsl:text>
		</span>
		<a href="{@xlink:href}">
			<xsl:choose>
				<xsl:when test="*|text()">
					<xsl:apply-templates/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@xlink:href"/>
				</xsl:otherwise>
			</xsl:choose>
		</a>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- product -->
	<!-- uses mode="product" within -->

	<xsl:template match="product" mode="front">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<span class="gen">
						<xsl:text>Product Information: </xsl:text>
					</span>
					<xsl:apply-templates mode="product"/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">
					<xsl:text>Product Information: </xsl:text>
				</span>
				<xsl:apply-templates mode="product"/>
			</xsl:otherwise>
		</xsl:choose>
		<br/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- The product element allows a mixed-content model,
     but perhaps sometimes only element nodes will be used.
     Rough test:
       - if the next sibling is another element,
         add a space to make the content somewhat legible. -->
	<xsl:template match="*" mode="product">

		<xsl:apply-templates/>

		<xsl:if test="generate-id(following-sibling::node()[1])
                 =generate-id(following-sibling::*[1])">
			<xsl:text> </xsl:text>
		</xsl:if>

	</xsl:template>

	<!-- copyright-statement, copyright-year, copyright-holder -->

	<xsl:template match="copyright-statement | copyright-year | copyright-holder" mode="front">
		<xsl:apply-templates/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>
	
	<!-- license whether or not part of permissions -->
	<xsl:template match="license" mode="front">
		<br/>
		<xsl:choose>
			<xsl:when test="@license-type">
				<span class="gen"><xsl:value-of select="@license-type"/>: </span>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">License: </span>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:apply-templates select="p/* | p/text()"/>
	</xsl:template>

	<!-- history -->

	<xsl:template match="history/date" mode="front">

		<xsl:variable name="the-type">
			<xsl:choose>
				<xsl:when test="@date-type='accepted'">Accepted</xsl:when>
				<xsl:when test="@date-type='received'">Received</xsl:when>
				<xsl:when test="@date-type='rev-request'">Revision Requested</xsl:when>
				<xsl:when test="@date-type='rev-recd'">Revision Received</xsl:when>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="@date-type">
			<span class="gen">
				<xsl:value-of select="$the-type"/>
				<xsl:text> </xsl:text>
			</span>
		</xsl:if>

		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- pub-date -->

	<xsl:template match="pub-date" mode="front">
		<xsl:choose>
			<xsl:when test="@pub-type='ppub'">
				<span class="gen">Print </span>
			</xsl:when>
			<xsl:when test="@pub-type='epub'">
				<span class="gen">Electronic </span>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="@pub-type"/>
			</xsl:otherwise>
		</xsl:choose>

		<span class="gen">
			<xsl:text> publication date: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- volume -->

	<xsl:template match="volume" mode="front">
		<span class="gen">
			<xsl:text>Volume: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<xsl:if test="../issue">
			<xsl:text> </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- issue -->

	<xsl:template match="issue" mode="front">
		<span class="gen">
			<xsl:text>Issue: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- elocation-id -->

	<xsl:template match="elocation-id" mode="front">
		<span class="gen">
			<xsl:text>E-location ID: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- fpage, lpage -->

	<xsl:template match="fpage" mode="front">
		<span class="gen">
			<xsl:text>First Page: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<xsl:choose>
			<xsl:when test="../lpage">
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<br/>
				<xsl:call-template name="nl-1"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="lpage" mode="front">
		<span class="gen">
			<xsl:text>Last Page: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- article-id -->

	<xsl:template match="article-id">
		<xsl:choose>
			<xsl:when test="@pub-id-type='coden'">
				<span class="gen">
					<xsl:text>Coden: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='doi'">
				<span class="gen">
					<xsl:text>DOI: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='medline'">
				<span class="gen">
					<xsl:text>Medline Id: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='pii'">
				<span class="gen">
					<xsl:text>Publisher Item Identifier: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='pmid'">
				<span class="gen">
					<xsl:text>PubMed Id: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='publisher-id'">
				<span class="gen">
					<xsl:text>Publisher Id: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='sici'">
				<span class="gen">
					<xsl:text>Serial Item and Contribution Identifier: </xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='doaj'">
				<span class="gen">
					<xsl:text>Directory of Open Access Journals</xsl:text>
				</span>
			</xsl:when>
			<xsl:when test="@pub-id-type='other'">
				<span class="gen">
					<xsl:text>Article Id: </xsl:text>
				</span>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">
					<xsl:text>ID: </xsl:text>
				</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>

	</xsl:template>

	<!-- contract-num, contract-sponsor -->

	<xsl:template match="contract-num | contract-sponsor" mode="front">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
					<br/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</span>
				<br/>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- custom-meta-wrap -->
	
	<xsl:template match="custom-meta-wrap">
		<xsl:for-each select="custom-meta">
			<span class="gen">
				<xsl:value-of select="meta-name"/>
				<xsl:text>: </xsl:text>
			</span>
			<xsl:value-of select="meta-value"/>
			<br/>
		</xsl:for-each>
	</xsl:template>
	
	



	<!-- ============================================================= -->
	<!--  36) TITLE-GROUP                                              -->
	<!-- ============================================================= -->

	<!-- title-group -->

	<!-- Appears only in article-meta -->
	<!-- The fn-group, if any, is output in the "back" of the
     HTML page, together with any other fn-group. -->

	<xsl:template match="title-group" mode="front">
		<div id="MainTitle">
			<xsl:apply-templates select="article-title" mode="front"/>
		</div>
			<xsl:apply-templates select="subtitle" mode="front"/>
			<xsl:apply-templates select="trans-title | alt-title" mode="front"/>
	</xsl:template>

	<xsl:template match="article-title" mode="front">
		<xsl:apply-templates/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- subtitle runs in with title -->
	<xsl:template match="subtitle" mode="front">
		<div id="SubTitle">
			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="trans-title" mode="front">
		<span class="tl-section-level">
			<span class="gen">Translated title: </span>
			<xsl:apply-templates/>
		</span>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="alt-title" mode="front">
		<span class="tl-default">
			<xsl:choose>
				<xsl:when test="@alt-title-type='right-running-head'">
					<span class="gen">Title for RRH: </span>
				</xsl:when>
				<xsl:otherwise>
					<span class="gen">Alternate Title: </span>
				</xsl:otherwise>
			</xsl:choose>

			<xsl:apply-templates/>
		</span>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  37) PARTS OF CONTRIB                                         -->
	<!-- ============================================================= -->

	<!-- collab -->
	<!-- A mixed-content model; process it as given -->

	<xsl:template match="collab" mode="front">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- name -->
	<!-- uses mode="contrib" within -->

	<xsl:template match="name" mode="front">
		<xsl:apply-templates select="prefix" mode="contrib"/>
		<xsl:apply-templates select="given-names" mode="contrib"/>
		<xsl:apply-templates select="surname" mode="contrib"/>
		<xsl:apply-templates select="suffix" mode="contrib"/>
	</xsl:template>

	<xsl:template match="prefix | given-names" mode="contrib">
		<xsl:apply-templates/>
		<xsl:text> </xsl:text>
	</xsl:template>

	<xsl:template match="surname" mode="contrib">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="suffix" mode="contrib">
		<xsl:text>, </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="degrees" mode="contrib">
		<xsl:text>, </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<!-- the formatting is sometimes in the source XML,
     e.g., <sup><italic>a</italic></sup> -->
	<xsl:template match="xref[@ref-type='author-notes']" mode="contrib">
		<span class="au-label">			
			<a href="#{@rid}">
				<xsl:choose>
					<xsl:when test="child::node()">
						<xsl:apply-templates/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="@rid"/>
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</span>
	</xsl:template>

	<!-- the formatting is sometimes in the source XML,
     e.g., <sup><italic>a</italic></sup> -->
	<xsl:template match="xref[@ref-type='aff']" mode="contrib">
		<span class="au-label">			
			<a href="#{@rid}">
				<xsl:choose>
					<xsl:when test="child::node()">
						<xsl:apply-templates/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="@rid"/>
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</span>
	</xsl:template>

	<!-- author-comment -->
	<!-- optional title, one-or-more paras -->

	<xsl:template match="author-comment" mode="front">

		<xsl:variable name="the-title">
			<xsl:choose>
				<xsl:when test="title">
					<xsl:apply-templates select="title" mode="front"/>
				</xsl:when>
				<xsl:when test="self::author-comment">
					<xsl:text>Author Comment: </xsl:text>
				</xsl:when>
				<!-- no logical otherwise -->
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:value-of select="$the-title"/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="make-id"/>
				<xsl:value-of select="$the-title"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:apply-templates select="*[not(self::title)]" mode="front"/>
	</xsl:template>

	<xsl:template match="author-comment/title" mode="front">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="bio/title" mode="front">
		<span class="author">
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<!-- author-comment/p and bio/p in HTML give too much vertical
     space for the display situation; so we force them to produce
     only breaks. -->
	<xsl:template match="author-comment/p" mode="front">
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- Biography section -->
	<xsl:template match="bio/p" mode="front">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- parts of contrib: address -->

	<xsl:template match="present-address" mode="front">
		<div id="Correspondence">
			<span class="subTitle2">
				<xsl:text>Address: </xsl:text>
			</span>

		<xsl:apply-templates mode="front"/>
		</div>
	</xsl:template>

	<xsl:template match="institution" mode="front">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="following-sibling::*">
			<xsl:text> </xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template match="address/*" mode="front">
		<xsl:apply-templates/>
		<xsl:if test="following-sibling::*">
			<xsl:text> </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- aff -->
	<!-- These affs are inside a contrib element -->
	<xsl:template match="aff" mode="front">
		<span class="gen">
			<xsl:call-template name="make-id"/>
			<xsl:text>Affiliation: </xsl:text>
		</span>

		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- aff -->
	<!-- These affs are NOT inside a contrib element -->
	<xsl:template match="aff" mode="aff-outside-contrib">
		<xsl:call-template name="make-id"/>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>



	<!-- on-behalf-of -->

	<xsl:template match="on-behalf-of" mode="front">
		<span class="gen">
			<xsl:text>On behalf of: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- role -->

	<xsl:template match="role" mode="front"/>

	<!-- email -->

	<xsl:template match="email" mode="front">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-href"/>
					<span class="gen">
						<xsl:text>Email: </xsl:text>
					</span>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="gen">
					<xsl:text>Email: </xsl:text>
				</span>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- author-notes -->

	<xsl:template match="author-notes" mode="front">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates mode="front"/>
	</xsl:template>

	<!-- author-notes/title -->

	<xsl:template match="author-notes/title" mode="front">
		<b>
			<xsl:apply-templates/>
		</b>
		<br/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- author-notes/corresp -->
	<!-- mixed-content; process it as given -->

	<xsl:template match="author-notes/corresp" mode="front">
		<div id="Correspondence">
			<span class="subTitle2">
				<xsl:text>Correspondence: </xsl:text>
			</span>
		<xsl:choose>
			<xsl:when test="contains(., 'Correspondence: ')">
				<xsl:value-of select="substring-after(.,': ')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>


		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- author-notes/fn -->
	<!-- optional label, one or more paras -->
	<!-- unmoded (author-notes only appears in article-meta) -->

	<xsl:template match="author-notes/fn" mode="front">
		<div id="Correspondence">
			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</div>
	</xsl:template>

	<!-- author-notes/fn/label -->

	<xsl:template match="author-notes/fn/label">
		<span class="subTitle2">
			<xsl:apply-templates/>
			<xsl:text>: </xsl:text>
		</span>
	</xsl:template>

	<!-- author-notes/fn/p[1] -->

	<xsl:template match="author-notes/fn/p[1]" priority="2">

		<span class="capture-id">
			<xsl:call-template name="make-id"/>

			<xsl:choose>
				<xsl:when test="preceding-sibling::label"/>
				<xsl:when test="parent::fn/@symbol">
					<sup>
						<xsl:value-of select="parent::fn/@symbol"/>
					</sup>
					<xsl:text> </xsl:text>
				</xsl:when>
				<xsl:when test="parent::fn/@fn-type='com'">
					<span class="gen">
						<xsl:text>Communicated by footnote: </xsl:text>
					</span>
				</xsl:when>
				<xsl:when test="parent::fn/@fn-type='con'">
					<span class="gen">
						<xsl:text>Contributed by footnote: </xsl:text>
					</span>
				</xsl:when>
				<xsl:when test="parent::fn/@fn-type='cor'">
					<span class="gen">
						<xsl:text>Correspondence: </xsl:text>
					</span>
				</xsl:when>
				<xsl:when test="parent::fn/@fn-type='financial-disclosure'">
					<span class="gen">
						<xsl:text>Financial Disclosure: </xsl:text>
					</span>
				</xsl:when>
				<xsl:when test="parent::fn/@fn-type">
					<span class="gen">
						<xsl:text>[</xsl:text>
						<xsl:value-of select="parent::fn/@fn-type"/>
						<xsl:text>]</xsl:text>
						<xsl:text> </xsl:text>
					</span>
				</xsl:when>
			</xsl:choose>

			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<!-- author-notes/fn/p processed as ordinary unmoded p-->

	<!-- abstract and trans-abstract are handled entirely
     within the make-front template -->


	<!-- ============================================================= -->
	<!-- BACK (unmoded templates)                                      -->
	<!-- ============================================================= -->

	<!-- ============================================================= -->
	<!--  38. BACK MATTER: ACKNOWLEDGEMENTS                            -->
	<!-- ============================================================= -->

	<xsl:template match="ack">

		<xsl:call-template name="nl-1"/>
		<xsl:if test="position()>1">
			<hr class="section-rule"/>
		</xsl:if>
		<xsl:call-template name="nl-1"/>

		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:if test="not(title)">
				<div class="Title1">Acknowledgments</div>
				<xsl:call-template name="nl-1"/>
			</xsl:if>

			<xsl:apply-templates/>

		</div>

	</xsl:template>

	<!-- ============================================================= -->
	<!--  39. BACK-MATTER: APPENDIX                                    -->
	<!-- ============================================================= -->

	<xsl:template match="app">


	<xsl:if test="not(fig) and not(media)">
	  <xsl:text>&#xA;</xsl:text>
		<div class="figure">
		<xsl:call-template name="make-id"/>
		<xsl:apply-templates/>
		<xsl:call-template name="nl-1"/>
	  </div>
	  </xsl:if>

	<xsl:if test="media">
		<xsl:text>&#xA;</xsl:text>
		<div class="figure">
		<xsl:call-template name="make-id"/>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{media/@xlink:href}">

			<img src="http://openmedicine.ca/images/figure.png" alt="{@label}" />
		</a>
		<span class="subTitle1">
			<xsl:value-of select="label"/>
			<xsl:text>. </xsl:text>
		</span>
		<xsl:value-of select="media/caption"/>
		<xsl:text> [</xsl:text>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{media/@xlink:href}">view</a>
		<xsl:text>]</xsl:text>
		<xsl:call-template name="nl-1"/>
		</div>
	</xsl:if>

	<xsl:if test="fig">
		<xsl:text>&#xA;</xsl:text>
		<div class="figure">
		<xsl:call-template name="make-id"/>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@id}.png">

			<img src="http://openmedicine.ca/images/figure.png" alt="{@label}" />
		</a>
		<span class="subTitle1">
			<xsl:value-of select="label"/>
			<xsl:text>. </xsl:text>
		</span>
		<xsl:value-of select="fig/caption"/>
		<xsl:text> [</xsl:text>
		<a href="/images/{/article/front/article-meta/volume}/{/article/front/article-meta/fpage}/{@id}.png">view</a>
		<xsl:text>]</xsl:text>
		<xsl:call-template name="nl-1"/>
		</div>
	</xsl:if>

	</xsl:template>

	<!-- ============================================================= -->
	<!--  40. BACK-MATTER: FOOTNOTE-GROUP and FN                       -->
	<!-- ============================================================= -->

	<xsl:template match="fn-group">

		<xsl:call-template name="nl-1"/>
		<xsl:if test="position()>1">
			<hr class="section-rule"/>
		</xsl:if>
		<xsl:call-template name="nl-1"/>

		<xsl:apply-templates/>
		<xsl:call-template name="nl-1"/>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  Footnote                                                     -->
	<!-- ============================================================= -->

	<!-- symbol or id is displayed by the first para within the fn     -->

	<xsl:template match="fn">
		<div>
			<xsl:if test="@id">
				<xsl:attribute name="id">
					<xsl:value-of select="@id"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select="p"/>
		</div>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  41. BACK-MATTER: NOTES                                       -->
	<!-- ============================================================= -->

	<xsl:template match="notes">

		<xsl:call-template name="nl-1"/>
		<xsl:if test="position()>1">
			<hr class="section-rule"/>
		</xsl:if>
		<xsl:call-template name="nl-1"/>

		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:if test="not(title)">
				<span class="tl-main-part">Notes</span>
				<xsl:call-template name="nl-1"/>
			</xsl:if>

			<xsl:apply-templates/>
			<xsl:call-template name="nl-1"/>
		</div>

	</xsl:template>

	<xsl:template match="note">
		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<small>
				<xsl:apply-templates/>
			</small>
		</span>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  42. BACK MATTER: GLOSSARY                                    -->
	<!-- ============================================================= -->

	<xsl:template match="glossary">

		<xsl:call-template name="nl-1"/>
		<xsl:if test="position()>1">
			<hr class="section-rule"/>
		</xsl:if>
		<xsl:call-template name="nl-1"/>

		<div class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:if test="not(title)">
				<span class="tl-main-part">
					<xsl:call-template name="make-id"/>
					<xsl:text>Glossary</xsl:text>
				</span>
				<xsl:call-template name="nl-1"/>
			</xsl:if>

			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>

	</xsl:template>

	<xsl:template match="gloss-group">

		<xsl:call-template name="nl-1"/>
		<xsl:if test="not(title)">
			<span class="tl-main-part">Glossary</span>
			<xsl:call-template name="nl-1"/>
		</xsl:if>

		<xsl:apply-templates/>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="def-list">
		<xsl:call-template name="nl-1"/>
		<xsl:call-template name="make-anchor"/>

		<table width="60%" cellpadding="2" class="bm">
			<xsl:call-template name="nl-1"/>

			<xsl:call-template name="table-setup-l-wide"/>
			<xsl:if test="title">
				<tr>
					<th colspan="2" align="left">
						<b><xsl:apply-templates select="title"/></b>
					</th>
				</tr>
			</xsl:if>
			<xsl:if test="term-head|def-head">
				<tr>
					<td valign="top" align="right">
						<i>
							<xsl:apply-templates select="term-head"/>
						</i>
					</td>
					<td valign="top">
						<i>
							<xsl:apply-templates select="def-head"/>
						</i>
					</td>
				</tr>
				<xsl:call-template name="nl-1"/>
			</xsl:if>

			<xsl:apply-templates select="def-item"/>

			<xsl:call-template name="nl-1"/>
			
		</table>
		
		<xsl:if test="def-list">
			<br/>
			<xsl:apply-templates select="def-list"/>
		</xsl:if>
		
		<xsl:call-template name="nl-1"/>

	</xsl:template>


	<xsl:template match="def-item">
		<tr>
			<xsl:call-template name="make-id"/>
			<xsl:call-template name="nl-1"/>
			<xsl:apply-templates/>
		</tr>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="term">
		<td valign="top" align="right">
			<xsl:call-template name="make-id"/>
			<b>
				<xsl:apply-templates/>
			</b>
		</td>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="def">
		<td valign="top">
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</td>
		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  43. TARGET OF A REFERENCE                                    -->
	<!-- ============================================================= -->

	<xsl:template match="target">
		<a>
			<xsl:call-template name="make-id"/>
			<xsl:apply-templates/>
		</a>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  44. XREFS                                                    -->
	<!-- ============================================================= -->

	<!-- xref for fn, table-fn, or bibr becomes a superior number -->
	<!-- Displays the @rid, not the element content (if any) -->

	<xsl:template
		match="xref[@ref-type='fn']
                  | xref[@ref-type='table-fn']
                  | xref[@ref-type='bibr']">
		<span class="xref">
			<xsl:call-template name="make-id"/>
			<sup>
				<!-- if immediately-preceding sibling was an xref, punctuate
           (otherwise assume desired punctuation is in the source).-->
				<xsl:if test="local-name(preceding-sibling::node()[1])='xref'">
					<span class="gen">
						<xsl:text>, </xsl:text>
					</span>
				</xsl:if>
				<a href="#{@rid}">
					<xsl:choose>
						<xsl:when test="child::node()">
							<xsl:apply-templates/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="@rid"/>
						</xsl:otherwise>
					</xsl:choose>
				</a>
			</sup>
		</span>
	</xsl:template>

	<xsl:template match="text()[normalize-space(.)='-']">
		<xsl:choose>
			<!-- if a hyphen is the only thing in a text node
         and it's between two xrefs, we conclude that
         it's expressing a range, and we superscript it -->
			<xsl:when
				test="local-name(following-sibling::node()[1])='xref'
                and local-name(preceding-sibling::node()[1])='xref' and following-sibling::xref[@ref-type='bibr']">
				<sup>-</sup>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="text()[normalize-space(.)=',']">
		<xsl:choose>
			<!-- if a comma is the only thing in a text node
         and it's between two xrefs, we conclude that
         it's expressing a range, and we superscript it -->
			<xsl:when
				test="local-name(following-sibling::node()[1])='xref'
                and local-name(preceding-sibling::node()[1])='xref' and following-sibling::xref[@ref-type='bibr']">
				<sup>,</sup>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="text()[normalize-space(.)='–']">
		<xsl:choose>
			<!-- if a dash is the only thing in a text node
         and it's between two xrefs, we conclude that
         it's expressing a range, and we superscript it -->
			<xsl:when
				test="local-name(following-sibling::node()[1])='xref'
                and local-name(preceding-sibling::node()[1])='xref' and following-sibling::xref[@ref-type='bibr']">
				<sup>,</sup>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- In xref of type fig or of type table,
     the element content is the figure/table number
     and typically part of a sentence,
     so -not- a superior number. -->
	<xsl:template match="xref[@ref-type='fig'] | xref[@ref-type='table']">
		<span class="xref">
			<xsl:call-template name="make-id"/>
			<a href="#{@rid}">
				<xsl:choose>
					<!-- if xref not empty -->
					<xsl:when test="child::node()">
						<xsl:apply-templates/>
					</xsl:when>
					<xsl:otherwise>
						<!-- if empty -->
						<xsl:value-of select="@rid"/>
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</span>
	</xsl:template>

	<!-- default: if none of the above ref-types -->
	<xsl:template match="xref">
		<span class="xref">
			<xsl:call-template name="make-id"/>
			<a href="#{@rid}">
				<xsl:choose>
					<!-- if xref not empty -->
					<xsl:when test="child::node()">
						<xsl:apply-templates/>
					</xsl:when>
					<xsl:otherwise>
						<!-- if empty -->
						<xsl:value-of select="@rid"/>
					</xsl:otherwise>
				</xsl:choose>
			</a>
		</span>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  45. EXTERNAL LINKS                                           -->
	<!-- ============================================================= -->

	<!-- xlink:href attribute makes a link -->

	<xsl:template match="ext-link | uri">
		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a target="xrefwindow" >
					<xsl:call-template name="make-href"/>
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="capture-id">
					<xsl:call-template name="make-id"/>
					<xsl:apply-templates/>
				</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- xlink:href attribute makes a link -->

	<xsl:template match="mailto">

		<xsl:choose>
			<xsl:when test="@xlink:href">
				<a>
					<xsl:call-template name="make-email"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  46. TITLES: MAIN ARTICLE DIVISIONS                           -->
	<!-- ============================================================= -->

	<!-- main or top-level divisions -->
	<xsl:template match="abstract/sec/p">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="abstract/sec">
		<xsl:call-template name="nl-1"/>
		<p>
		<xsl:apply-templates/>
		</p>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template match="abstract/sec/title">
		<span class="subTitle2">
			<xsl:apply-templates/>
		<xsl:text>:</xsl:text>
		</span>
		<xsl:text> </xsl:text>
	</xsl:template>
	<xsl:template
		match="body/sec/title
                   | back/title | app-group/title | app/title
                   | glossary/title | ack/title
                   | ref-list/title | back/notes/title">
		<xsl:call-template name="nl-1"/>
		<div class="Title1">
			<xsl:apply-templates/>
		</div>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<xsl:template
		match="body/sec/sec/title">
		<xsl:choose>
			<xsl:when test="local-name(following-sibling::node()[1])='sec'">
				<span class="subTitle1">
					<xsl:apply-templates/>
				</span>
				<xsl:call-template name="nl-1"/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template
		match="body/sec/sec/sec/title">
		<xsl:choose>
			<xsl:when test="local-name(following-sibling::node()[1])='sec'">
				<em>
					<xsl:apply-templates/>
				</em>
				<xsl:call-template name="nl-1"/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template
		match="body/sec/sec/sec/sec/title">
		<xsl:choose>
			<xsl:when test="local-name(following-sibling::node()[1])='sec'">
				<xsl:apply-templates/>
				<xsl:call-template name="nl-1"/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="app-group">
		<xsl:call-template name="nl-1"/>
		<xsl:if test="not(title)">
			<div class="Title1">
				<xsl:text>Appendix</xsl:text>
			</div>
		</xsl:if>
		<xsl:call-template name="nl-1"/>
		<xsl:apply-templates/>
	</xsl:template>
	<!-- ============================================================= -->
	<!--  47. TITLES: FIRST-LEVEL DIVISIONS AND DEFAULT                -->
	<!-- ============================================================= -->

	<!-- first-level divisions and default -->

	<xsl:template
		match="ack/sec/title | app/sec/title
                   | boxed-text/title | gloss-group/title | notes/sec/title">
		<xsl:call-template name="nl-1"/>
		<span class="subTitle1">
			<xsl:apply-templates/>
		</span>
		<xsl:call-template name="nl-1"/>
	</xsl:template>
	
	<xsl:template match="list/title">
		<span class="li-title">
			<xsl:apply-templates/>
		</span>
	</xsl:template>
	
	<xsl:template match="def-list/title">
		<b><xsl:apply-templates/></b>
	</xsl:template>


	<!-- default: any other titles found -->

	<xsl:template match="title">
		<xsl:call-template name="nl-1"/>
		<span class="subTitle1">
			<xsl:apply-templates/>
		</span>
		<xsl:call-template name="nl-1"/>
	</xsl:template>

	<!-- ============================================================= -->
	<!--  49. UNMODED DATA ELEMENTS: MISCELLANEOUS                     -->
	<!-- ============================================================= -->

	<!-- epage -->

	<xsl:template match="epage">
		<span class="gen">
			<xsl:text>Electronic Page: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<br/>
	</xsl:template>

	<!-- series -->

	<xsl:template match="series">
		<xsl:text> (</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>).</xsl:text>
	</xsl:template>

	<!-- comment -->

	<xsl:template match="comment">
		<xsl:if test="not(self::node()='.')">
			<xsl:text> </xsl:text>
			<xsl:apply-templates/>
			<xsl:text>. </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- annotation -->

	<xsl:template match="annotation">
		<br/>
		<xsl:text> [</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>]</xsl:text>
		<br/>
	</xsl:template>


	<!-- permissions -->

	<xsl:template match="permissions">
		<xsl:choose>
			<xsl:when test="copyright-statement">
				<xsl:apply-templates select="copyright-statement"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:if test="copyright-year">
					<p>
						<span class="gen">
							<xsl:text>Copyright: </xsl:text>
						</span>
						<xsl:apply-templates select="copyright-year"/>
						<xsl:apply-templates select="copyright-holder"/>
					</p>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- copyright-statement whether or not part of permissions -->

	<xsl:template match="copyright-statement">
		<p>
			<xsl:apply-templates/>
		</p>
	</xsl:template>
	
	<!-- license whether or not part of permissions -->
	<xsl:template match="license">
		<p>
			<xsl:if test="@license-type">
				<span class="gen">[<xsl:value-of select="@license-type"/>] </span>
			</xsl:if>
			<xsl:apply-templates select="p/* | p/text()"/>
		</p>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  50. UNMODED DATA ELEMENTS: PARTS OF A DATE                   -->
	<!-- ============================================================= -->

	<xsl:template match="day">
		<span class="gen">
			<xsl:text>Day: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<xsl:text> </xsl:text>
	</xsl:template>

	<xsl:template match="month">
		<span class="gen">
			<xsl:text>Month: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<xsl:text> </xsl:text>
	</xsl:template>

	<xsl:template match="season">
		<span class="gen">
			<xsl:text>Season: </xsl:text>
		</span>
		<xsl:apply-templates/>
		<xsl:text> </xsl:text>
	</xsl:template>

	<xsl:template match="year">
		<xsl:apply-templates/>
		<xsl:text>;</xsl:text>
	</xsl:template>

	<xsl:template match="stringdate">
		<span class="gen">
			<xsl:text>Stringdate: </xsl:text>
		</span>
		<xsl:apply-templates/>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  51. UNMODED DATA ELEMENTS: PARTS OF A NAME                   -->
	<!-- ============================================================= -->


	<xsl:template match="name">

	<xsl:variable name="nodetotal">
		<xsl:choose>
			<xsl:when test="count(../*) &lt; 7">
				<xsl:value-of select="count(../*)" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="7" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

		<xsl:variable name="position" select="position()"/>
	<xsl:if test="position()=7">
		<xsl:text>et al. </xsl:text>
	</xsl:if>

	<xsl:if test="position()&lt;7">
		<xsl:choose>
			<xsl:when test="given-names">

				<xsl:apply-templates select="surname"/>
				<xsl:text> </xsl:text>
				<xsl:call-template name="initials">
					<xsl:with-param name="name" select="given-names"/>
				</xsl:call-template>
				<xsl:if test="suffix">
					<xsl:text> </xsl:text>
					<xsl:apply-templates select="suffix"/>
				</xsl:if>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates select="surname"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="following-sibling::aff"/>
			<xsl:when test="../following-sibling::collab and $nodetotal=$position">
				<xsl:text>; </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$nodetotal=$position">
						<xsl:choose>
							<xsl:when test="parent::person-group/@person-group-type">
								<xsl:choose>
									<xsl:when test="parent::person-group/@person-group-type='author'">
										<xsl:text>. </xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:text>, </xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>. </xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>, </xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:if>
	</xsl:template>

	<xsl:template match="aff">
		<xsl:variable name="nodetotal" select="count(../*)"/>
		<xsl:variable name="position" select="position()"/>

		<span class="capture-id">
			<xsl:call-template name="make-id"/>
			<xsl:text> (</xsl:text>
			<xsl:apply-templates/>
			<xsl:text>)</xsl:text>
		</span>

		<xsl:choose>
			<xsl:when test="$nodetotal=$position">. </xsl:when>
			<xsl:otherwise>, </xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<xsl:template match="etal">
		<xsl:text>et al. </xsl:text>
		<xsl:choose>

			<xsl:when test="parent::person-group/@person-group-type">
				<xsl:choose>
					<xsl:when test="parent::person-group/@person-group-type='author'">
						<xsl:text> </xsl:text>
					</xsl:when>
					<xsl:otherwise/>
				</xsl:choose>
			</xsl:when>

			<xsl:otherwise>
				<xsl:text> </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  CITATION AND NLM-CITATION                                    -->
	<!-- ============================================================= -->


	<!-- NLM Archiving DTD:
       - citation uses mode nscitation.

     NLM Publishing DTD:
       - nlm-citation uses several modes,
         including book, edited-book, conf, and "none".
-->


	<!-- ============================================================= -->
	<!--  52. BACK MATTER: REF-LIST                                    -->
	<!-- ============================================================= -->

	<xsl:template match="ref-list">

		<xsl:choose>
			<xsl:when test="not(title)">
				<div class="Title1">References</div>
				<xsl:call-template name="nl-1"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="title"/>
			</xsl:otherwise>
		</xsl:choose>

		<ol>
			<xsl:apply-templates select="*[not(self::title)]"/>
		</ol>

		<xsl:call-template name="nl-1"/>
	</xsl:template>


	<!-- suppress the ref-list title so it doesn't reappear -->
	<xsl:template match="ref-list/title" mode="nscitation"/>



	<!-- ============================================================= -->
	<!--  53. REF                                                      -->
	<!-- ============================================================= -->

	<!-- If ref/label, ref is a table row;
		If count(ref/citation) > 1, each citation is a table row -->
	<xsl:template match="ref">
		<li id="{@id}">
			<xsl:apply-templates select="citation|nlm-citation"/>
		</li>
		<xsl:call-template name="nl-1"/>
	</xsl:template>
	
	<!-- becomes content of table cell, column 1-->
	<xsl:template match="ref/label | citation/label">
		<b>
			<i>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</i>
		</b>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  54. CITATION (for NLM Archiving DTD)                         -->
	<!-- ============================================================= -->

	<!-- The citation model is mixed-context, so it is processed
     with an apply-templates (as for a paragraph)
       -except-
     if there is no PCDATA (only elements), spacing and punctuation
     also must be supplied = mode nscitation. -->

	<xsl:template match="ref/citation">
		<xsl:choose>
			<!-- if has no significant text content, presume that
           punctuation is not supplied in the source XML
           = transform will supply it. -->
			<xsl:when test="not(text()[normalize-space()])">
				<xsl:apply-templates mode="none"/>
			</xsl:when>

			<!-- mixed-content, processed as paragraph -->
			<xsl:otherwise>
				<xsl:apply-templates mode="nscitation"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<!-- ============================================================= -->
	<!--  55. NLM-CITATION (for NLM Publishing DTD)                    -->
	<!-- ============================================================= -->

	<!-- The nlm-citation model allows only element content, so
     it takes a pull template and adds punctuation. -->

	<!-- Processing of nlm-citation uses several modes, including
     citation, book, edited-book, conf, inconf, and mode "none".   -->

	<!-- Each citation-type is handled in its own template. -->


	<!-- Book or thesis -->
	<xsl:template
		match="ref/nlm-citation[@citation-type='book']
                   | ref/nlm-citation[@citation-type='thesis']
                   | ref/citation[@citation-type='book']
                   | ref/citation[@citation-type='thesis']">

		<xsl:variable name="augroupcount" select="count(person-group) + count(collab)"/>

		<xsl:choose>

			<xsl:when
				test="$augroupcount>1 and
                    person-group[@person-group-type!='author'] and
                    article-title ">
				<xsl:apply-templates select="person-group[@person-group-type='author']" mode="book"/>
				<xsl:apply-templates select="collab" mode="book"/>
				<xsl:apply-templates select="article-title" mode="editedbook"/>
				<xsl:text>In: </xsl:text>
				<xsl:apply-templates
					select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='allauthors']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
					mode="book"/>
				<xsl:apply-templates select="source" mode="book"/>
				<xsl:apply-templates select="edition" mode="book"/>
				<xsl:apply-templates select="volume" mode="book"/>
				<xsl:apply-templates select="trans-source" mode="book"/>
				<xsl:apply-templates select="publisher-name | publisher-loc" mode="none"/>
				<xsl:apply-templates select="year | time-stamp | season" mode="book"/>
				<xsl:apply-templates select="fpage | lpage" mode="book"/>
				<xsl:apply-templates select="access-date" mode="book"/>
			</xsl:when>

			<xsl:when
				test="person-group[@person-group-type='author'] or
                    person-group[@person-group-type='compiler']">
				<xsl:apply-templates
					select="person-group[@person-group-type='author']
                                 | person-group[@person-group-type='compiler']"
					mode="book"/>
				<xsl:apply-templates select="collab" mode="book"/>
				<xsl:apply-templates select="article-title" mode="book"/>
				<xsl:apply-templates select="source" mode="book"/>
				<xsl:apply-templates select="edition" mode="book"/>
				<xsl:apply-templates
					select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
					mode="book"/>
				<xsl:apply-templates select="volume" mode="book"/>
				<xsl:apply-templates select="trans-source" mode="book"/>
				<xsl:apply-templates select="publisher-name | publisher-loc" mode="none"/>
				<xsl:apply-templates select="year | time-stamp | season" mode="book"/>
				<xsl:apply-templates select="fpage | lpage" mode="book"/>
				<xsl:apply-templates select="access-date" mode="book"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates
					select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed']
                                 | person-group[@person-group-type='guest-editor']"
					mode="book"/>
				<xsl:apply-templates select="collab" mode="book"/>
				<xsl:apply-templates select="source" mode="book"/>
				<xsl:apply-templates select="edition" mode="book"/>
				<xsl:apply-templates select="volume" mode="book"/>
				<xsl:apply-templates select="trans-source" mode="book"/>
				<xsl:apply-templates select="publisher-name | publisher-loc" mode="none"/>
				<xsl:apply-templates select="year | month | time-stamp | season" mode="book"/>
				<xsl:apply-templates select="article-title | fpage | lpage" mode="book"/>
				<xsl:apply-templates select="access-date" mode="book"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:call-template name="citation-tag-ends"/>
	</xsl:template>


	<!-- Conference proceedings -->
	<xsl:template match="ref/nlm-citation[@citation-type='confproc'] | ref/citation[@citation-type='confproc']">

		<xsl:variable name="augroupcount" select="count(person-group) + count(collab)"/>

		<xsl:choose>
			<xsl:when test="$augroupcount>1 and person-group[@person-group-type!='author']">
				<xsl:apply-templates select="person-group[@person-group-type='author']" mode="book"/>
				<xsl:apply-templates select="collab"/>
				<xsl:apply-templates select="article-title" mode="inconf"/>
				<xsl:text>In: </xsl:text>
				<xsl:apply-templates
					select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='allauthors']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
					mode="book"/>
				<xsl:apply-templates select="source" mode="conf"/>
				<xsl:apply-templates select="conf-name | conf-date | conf-loc" mode="conf"/>
				<xsl:apply-templates select="publisher-loc" mode="none"/>
				<xsl:apply-templates select="publisher-name" mode="none"/>
				<xsl:apply-templates select="year | time-stamp | season" mode="book"/>
				<xsl:apply-templates select="fpage | lpage" mode="book"/>
				<xsl:apply-templates select="access-date" mode="book"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates select="person-group" mode="book"/>
				<xsl:apply-templates select="collab" mode="book"/>
				<xsl:apply-templates select="article-title" mode="conf"/>
				<xsl:apply-templates select="source" mode="conf"/>
				<xsl:apply-templates select="conf-name | conf-date | conf-loc" mode="conf"/>
				<xsl:apply-templates select="publisher-loc" mode="none"/>
				<xsl:apply-templates select="publisher-name" mode="none"/>
				<xsl:apply-templates select="year | time-stamp | season" mode="book"/>
				<xsl:apply-templates select="fpage | lpage" mode="book"/>
				<xsl:apply-templates select="access-date" mode="book"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:call-template name="citation-tag-ends"/>
	</xsl:template>


	<!-- Government and other reports, other, web, and commun -->
	<xsl:template
		match="ref/nlm-citation[@citation-type='gov']
                   | ref/nlm-citation[@citation-type='web']
                   | ref/nlm-citation[@citation-type='commun']
                   | ref/nlm-citation[@citation-type='other']
                   | ref/citation[@citation-type='gov']
                   | ref/citation[@citation-type='web']
                   | ref/citation[@citation-type='commun']
                   | ref/citation[@citation-type='other']">

		<xsl:apply-templates select="person-group" mode="book"/>

		<xsl:apply-templates select="collab"/>


		<xsl:apply-templates select="article-title|gov" mode="book"/>
		<xsl:apply-templates select="source" mode="book"/>
		<xsl:apply-templates select="edition"/>
		<xsl:apply-templates select="publisher-loc" mode="none"/>
		<xsl:apply-templates select="publisher-name" mode="none"/>
		<xsl:apply-templates select="year | month | time-stamp | season" mode="book"/>
		<xsl:apply-templates select="fpage | lpage" mode="book"/>
		<xsl:apply-templates select="access-date" mode="book"/>
		<xsl:apply-templates select="uri"/>
		<xsl:call-template name="citation-tag-ends"/>

	</xsl:template>


	<!-- Patents  -->
	<xsl:template match="ref/nlm-citation[@citation-type='patent'] | ref/citation[@citation-type='patent']">

		<xsl:apply-templates select="person-group" mode="book"/>
		<xsl:apply-templates select="collab" mode="book"/>
		<xsl:apply-templates select="article-title | trans-title" mode="none"/>
		<xsl:apply-templates select="source" mode="none"/>
		<xsl:apply-templates select="patent" mode="none"/>
		<xsl:apply-templates select="year | time-stamp | season | access-date" mode="book"/>
		<xsl:apply-templates select="fpage | lpage" mode="book"/>

		<xsl:call-template name="citation-tag-ends"/>

	</xsl:template>


	<!-- Discussion  -->
	<xsl:template match="ref/nlm-citation[@citation-type='discussion'] | ref/citation[@citation-type='discussion']">

		<xsl:apply-templates select="person-group" mode="book"/>
		<xsl:apply-templates select="collab"/>
		<xsl:apply-templates select="article-title" mode="editedbook"/>
		<xsl:text>In: </xsl:text>
		<xsl:apply-templates select="source" mode="none"/>

		<xsl:if test="publisher-name | publisher-loc">
			<xsl:text> [</xsl:text>
			<xsl:apply-templates select="publisher-loc" mode="none"/>
			<xsl:value-of select="publisher-name"/>
			<xsl:text>]; </xsl:text>
		</xsl:if>

		<xsl:apply-templates select="year | time-stamp | season | access-date" mode="book"/>
		<xsl:apply-templates select="fpage | lpage" mode="book"/>

		<xsl:call-template name="citation-tag-ends"/>
	</xsl:template>


	<!-- If none of the above citation-types applies,
     use mode="none". This generates punctuation. -->
	<!-- (e.g., citation-type="journal"              -->
	<xsl:template match="nlm-citation | citation">

		<xsl:apply-templates
			select="*[not(self::annotation) and
                                 not(self::edition) and
                                 not(self::lpage) and
                                 not(self::comment)]|text()"
			mode="none"/>

		<xsl:call-template name="citation-tag-ends"/>

	</xsl:template>


	<!-- ============================================================= -->
	<!-- person-group, mode=book                                       -->
	<!-- ============================================================= -->

	<xsl:template match="person-group" mode="book">

		<!-- XX needs fix, value is not a nodeset on the when -->
		<!--
  <xsl:choose>

    <xsl:when test="@person-group-type='editor'
                  | @person-group-type='assignee'
                  | @person-group-type='translator'
                  | @person-group-type='transed'
                  | @person-group-type='guest-editor'
                  | @person-group-type='compiler'
                  | @person-group-type='inventor'
                  | @person-group-type='allauthors'">

      <xsl:call-template name="make-persons-in-mode"/>
      <xsl:call-template name="choose-person-type-string"/>
      <xsl:call-template name="choose-person-group-end-punct"/>

    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates mode="book"/>
    </xsl:otherwise>

  </xsl:choose>
-->

		<xsl:call-template name="make-persons-in-mode"/>
		<xsl:call-template name="choose-person-type-string"/>
		<xsl:call-template name="choose-person-group-end-punct"/>

	</xsl:template>

	<xsl:template name="make-persons-in-mode">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template name="choose-person-type-string">

		<xsl:variable name="person-group-type">
			<xsl:value-of select="@person-group-type"/>
		</xsl:variable>

		<xsl:choose>
			<!-- allauthors is an exception to the usual choice pattern -->
			<xsl:when test="$person-group-type='allauthors'"/>

			<!-- the usual choice pattern: singular or plural? -->
			<xsl:when test="count(name) > 1 or etal ">
				<xsl:value-of select="($person-strings[@source=$person-group-type]/@plural)"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:value-of select="($person-strings[@source=$person-group-type]/@singular)"/>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<xsl:template name="choose-person-group-end-punct">

		<xsl:choose>
			<!-- compiler is an exception to the usual choice pattern -->
			<xsl:when test="@person-group-type='compiler'">
				<xsl:text>. </xsl:text>
			</xsl:when>
			<xsl:when test="@person-group-type='editor'">
				<xsl:text>. </xsl:text>
			</xsl:when>

			<!-- the usual choice pattern: semi-colon or period? BROKEN!!
			<xsl:when test="following-sibling::collab">
				<xsl:text>; </xsl:text>
			</xsl:when>
-->
			<xsl:otherwise/>
		</xsl:choose>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  56. Citation subparts (mode "none" separately at end)        -->
	<!-- ============================================================= -->

	<!-- names -->

	<xsl:template match="name" mode="nscitation">
		<xsl:value-of select="surname"/>
		<xsl:text>, </xsl:text>
		<xsl:value-of select="given-names"/>
		<xsl:text>. </xsl:text>
	</xsl:template>


	<xsl:template match="name" mode="book">
		<xsl:variable name="nodetotal" select="count(../*)"/>
		<xsl:variable name="penult" select="count(../*)-1"/>
		<xsl:variable name="position" select="position()"/>

		<xsl:choose>

			<!-- if given-names -->
			<xsl:when test="given-names">
				<xsl:apply-templates select="surname"/>
				<xsl:text> </xsl:text>
				<xsl:call-template name="firstnames">
					<xsl:with-param name="nodetotal" select="$nodetotal"/>
					<xsl:with-param name="position" select="$position"/>
					<xsl:with-param name="names" select="given-names"/>
					<xsl:with-param name="pgtype">
						<xsl:choose>
							<xsl:when test="parent::person-group[@person-group-type]">
								<xsl:value-of select="parent::person-group/@person-group-type"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="'author'"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
				</xsl:call-template>

				<xsl:if test="suffix">
					<xsl:text>, </xsl:text>
					<xsl:apply-templates select="suffix"/>
				</xsl:if>
			</xsl:when>

			<!-- if no given-names -->
			<xsl:otherwise>
				<xsl:apply-templates select="surname"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<!-- if have aff -->
			<xsl:when test="following-sibling::aff"/>

			<!-- if don't have aff -->
			<xsl:otherwise>
				<xsl:choose>

					<!-- if part of person-group -->
					<xsl:when test="parent::person-group/@person-group-type">
						<xsl:choose>

							<!-- if author -->
							<xsl:when test="parent::person-group/@person-group-type='author'">
								<xsl:choose>
									<xsl:when test="$nodetotal=$position">. </xsl:when>
									<xsl:when test="$penult=$position">
										<xsl:choose>
											<xsl:when test="following-sibling::etal">, </xsl:when>
											<xsl:otherwise>, </xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>, </xsl:otherwise>
								</xsl:choose>
							</xsl:when>

							<!-- if not author -->
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="$nodetotal=$position"/>
									<xsl:when test="$penult=$position">
										<xsl:choose>
											<xsl:when test="following-sibling::etal">, </xsl:when>
											<xsl:otherwise>, </xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>, </xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>

					<!-- if not part of person-group -->
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="$nodetotal=$position">. </xsl:when>
							<xsl:when test="$penult=$position">
								<xsl:choose>
									<xsl:when test="following-sibling::etal">, </xsl:when>
									<xsl:otherwise>, </xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>, </xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>

		</xsl:choose>
	</xsl:template>


	<xsl:template match="collab" mode="book">
		<xsl:apply-templates/>
		<xsl:if test="@collab-type='compilers'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:if test="@collab-type='assignee'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:text>. </xsl:text>
	</xsl:template>

	<xsl:template match="etal" mode="book">
		<xsl:text>et al. </xsl:text>
		<xsl:choose>
			<xsl:when test="parent::person-group/@person-group-type">
				<xsl:choose>
					<xsl:when test="parent::person-group/@person-group-type='author'">
						<xsl:text> </xsl:text>
					</xsl:when>
					<xsl:otherwise/>
				</xsl:choose>
			</xsl:when>

			<xsl:otherwise>
				<xsl:text> </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- affiliations -->

	<xsl:template match="aff" mode="book">
		<xsl:variable name="nodetotal" select="count(../*)"/>
		<xsl:variable name="position" select="position()"/>

		<xsl:text> (</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>)</xsl:text>

		<xsl:choose>
			<xsl:when test="$nodetotal=$position">. </xsl:when>
			<xsl:otherwise>, </xsl:otherwise>
		</xsl:choose>
	</xsl:template>



	<!-- publication info -->

	<xsl:template match="article-title" mode="nscitation">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>
		<xsl:choose>
			<xsl:when test="../fpage or ../lpage">
				<xsl:apply-templates/>
				<xsl:text>; </xsl:text>
			</xsl:when>
			<xsl:when test="contains('?', $last-char) or contains('!', $last-char)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="../access-date and not(../year) and not(../publisher-loc) and not(../publisher-name) and not(../source) and not(../fpage) and not(../lpage)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="article-title" mode="book">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>
		<xsl:choose>
			<xsl:when test="../fpage or ../lpage">
				<xsl:apply-templates/>
				<xsl:text>; </xsl:text>
			</xsl:when>
			<xsl:when test="contains('?', $last-char) or contains('!', $last-char)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="../access-date and not(../year) and not(../publisher-loc) and not(../publisher-name) and not(../source) and not(../fpage) and not(../lpage)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="article-title" mode="editedbook">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>
		<xsl:choose>
<!--
Caused wrong punctuation before editor list.
			<xsl:when test="../fpage or ../lpage">
				<xsl:apply-templates/>
				<xsl:text>; </xsl:text>
			</xsl:when>
-->
			<xsl:when test="contains('?', $last-char) or contains('!', $last-char)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="../access-date and not(../year) and not(../publisher-loc) and not(../publisher-name) and not(../source) and not(../fpage) and not(../lpage)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="article-title" mode="conf">
		<xsl:apply-templates/>
		<xsl:choose>
			<xsl:when test="../conf-name">
				<xsl:text>. </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>; </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="article-title" mode="inconf">
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>



	<xsl:template match="source" mode="nscitation">
		<i>
			<xsl:apply-templates/>
		</i>
	</xsl:template>

	<xsl:template match="source" mode="book">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>
		<xsl:choose>

			<xsl:when test="../trans-source">
				<i><xsl:apply-templates/></i>
				<xsl:choose>
					<xsl:when test="../volume | ../edition">
						<xsl:text>. </xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text> </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>

			<xsl:when test="contains('?', $last-char) or contains('!', $last-char) or contains(';', $last-char)">
				<i><xsl:apply-templates/></i>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<i><xsl:apply-templates/></i>
				<xsl:choose>
					<xsl:when test="following-sibling::access-date and not(../publisher-name) and not(../publisher-loc)">
						<xsl:text> </xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>. </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="source" mode="conf">
		<i><xsl:apply-templates/></i>
		<xsl:text>; </xsl:text>
	</xsl:template>

	<xsl:template match="trans-source" mode="book">
		<xsl:text> [</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>]. </xsl:text>
	</xsl:template>

	<xsl:template match="volume" mode="nscitation">
		<xsl:text> </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="volume | edition" mode="book">
		<xsl:apply-templates/>
		<xsl:if test="@collab-type='compilers'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:if test="@collab-type='assignee'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:text>. </xsl:text>
	</xsl:template>

	<!-- dates -->

	<xsl:template match="month" mode="nscitation"/>

	<xsl:template match="month"/>

	<xsl:template match="month" mode="none"/>



	<xsl:template match="month" mode="book">
		<xsl:variable name="month" select="."/>
		<xsl:choose>
			<xsl:when test="$month='01' or $month='1' or $month='January'">Jan</xsl:when>
			<xsl:when test="$month='02' or $month='2' or $month='February'">Feb</xsl:when>
			<xsl:when test="$month='03' or $month='3' or $month='March'">Mar</xsl:when>
			<xsl:when test="$month='04' or $month='4' or $month='April'">Apr</xsl:when>
			<xsl:when test="$month='05' or $month='5' or $month='May'">May</xsl:when>
			<xsl:when test="$month='06' or $month='6' or $month='June'">Jun</xsl:when>
			<xsl:when test="$month='07' or $month='7' or $month='July'">Jul</xsl:when>
			<xsl:when test="$month='08' or $month='8' or $month='August'">Aug</xsl:when>
			<xsl:when test="$month='09' or $month='9' or $month='September'">Sept</xsl:when>
			<xsl:when test="$month='10' or $month='October'">Oct</xsl:when>
			<xsl:when test="$month='11' or $month='November'">Nov</xsl:when>
			<xsl:when test="$month='12' or $month='December'">Dec</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$month"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="../day">
			<xsl:text> </xsl:text>
			<xsl:value-of select="../day"/>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="../time-stamp">
				<xsl:text>, </xsl:text>
				<xsl:value-of select="../time-stamp"/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="../access-date"/>
			<xsl:otherwise>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="day" mode="nscitation">
		<xsl:apply-templates/>
	</xsl:template>


	<xsl:template match="year" mode="nscitation">
		<xsl:text> </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="year" mode="book">
		<xsl:choose>
			<xsl:when test="../season or ../month">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:when test="../access-date and not(../lpage) and not(../fpage)">
				<xsl:apply-templates/>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>



	<xsl:template match="time-stamp" mode="nscitation">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="time-stamp" mode="book"/>


	<xsl:template match="access-date" mode="nscitation">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="access-date" mode="book">
		<xsl:text> (</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>). </xsl:text>
	</xsl:template>



	<xsl:template match="season" mode="book">
		<xsl:apply-templates/>
		<xsl:if test="@collab-type='compilers'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:if test="@collab-type='assignee'">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>
		<xsl:text>. </xsl:text>
	</xsl:template>



	<!-- pages -->

	<xsl:template match="fpage" mode="nscitation">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="fpage" mode="book">

		<xsl:text>p. </xsl:text>
		<xsl:apply-templates/>
	<xsl:choose>
		<xsl:when test="../lpage or ../access-date"/>
		<xsl:otherwise>
			<xsl:text>. </xsl:text>
		</xsl:otherwise>
	</xsl:choose>
	</xsl:template>


	<xsl:template match="lpage" mode="book">
		<xsl:choose>
			<xsl:when test="../fpage">
				<xsl:text>–</xsl:text>
				<xsl:apply-templates/>
				<xsl:if test="not(../access-date)">
					<xsl:text>. </xsl:text>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text> p.</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="lpage" mode="nscitation">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- misc stuff -->

	<xsl:template match="pub-id" mode="nscitation">
	<xsl:apply-templates select="." mode="none"/>	<xsl:text> [</xsl:text>
		<xsl:value-of select="@pub-id-type"/>
		<xsl:text>: </xsl:text>
		<xsl:apply-templates/>
		<xsl:text>]</xsl:text>
	</xsl:template>

	<xsl:template match="pub-id" mode="none">

		<xsl:variable name="pub-id" select="."/>
		<xsl:variable name="href">
			<xsl:choose>
				<xsl:when test="@pub-id-type='medline' or @pub-id-type='pmid'">
					<xsl:value-of select="'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;dopt=abstract&amp;list_uids='"/>
				</xsl:when>
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

				<xsl:choose>
					<xsl:when test="@pub-id-type='medline' or @pub-id-type='pmid'">
						<xsl:text>PubMed</xsl:text>
					</xsl:when>
					<xsl:when test="@pub-id-type='doi'">
						<xsl:text>CrossRef</xsl:text>
					</xsl:when>
				</xsl:choose>
			</a>
			<xsl:text>] </xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template match="annotation" mode="nscitation">
		<blockquote>
			<xsl:apply-templates/>
		</blockquote>
	</xsl:template>

	<xsl:template match="comment" mode="nscitation">
		<xsl:if test="not(self::node()='.')">
			<br/>
			<small>
				<xsl:apply-templates/>
			</small>
		</xsl:if>
	</xsl:template>


	<xsl:template match="conf-name | conf-date" mode="conf">
		<xsl:apply-templates/>
		<xsl:text>; </xsl:text>
	</xsl:template>

	<xsl:template match="conf-loc" mode="conf">
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>


	<!-- All formatting elements in citations processed normally -->
	<xsl:template match="bold | monospace | overline | sc | strike | sub |sup | underline" mode="nscitation">
		<xsl:apply-templates select="."/>
	</xsl:template>
	
	<xsl:template match="bold | monospace | overline | sc | strike | sub |sup | underline" mode="none">
		<xsl:apply-templates select="."/>
	</xsl:template>
	

	<!-- ============================================================= -->
	<!--  "firstnames"                                                 -->
	<!-- ============================================================= -->

	<!-- called by match="name" in book mode,
     as part of citation handling
     when given-names is not all-caps -->

	<xsl:template name="firstnames">
		<xsl:param name="nodetotal"/>
		<xsl:param name="position"/>
		<xsl:param name="names"/>
		<xsl:param name="pgtype"/>



		<xsl:variable name="length" select="string-length($names)-1"/>
		<xsl:variable name="gnm" select="substring($names,$length,2)"/>
		<xsl:variable name="GNM">
			<xsl:call-template name="capitalize">
				<xsl:with-param name="str" select="substring($names,$length,2)"/>
			</xsl:call-template>
		</xsl:variable>

		<!--
<xsl:text>Value of $names = [</xsl:text><xsl:value-of select="$names"/><xsl:text>]</xsl:text>
<xsl:text>Value of $length = [</xsl:text><xsl:value-of select="$length"/><xsl:text>]</xsl:text>
<xsl:text>Value of $gnm = [</xsl:text><xsl:value-of select="$gnm"/><xsl:text>]</xsl:text>
<xsl:text>Value of $GNM = [</xsl:text><xsl:value-of select="$GNM"/><xsl:text>]</xsl:text>
-->

		<xsl:if test="$names">
			<xsl:choose>

				<xsl:when test="$gnm=$GNM">
				<xsl:call-template name="initials">
					<xsl:with-param name="name" select="$names"/>
				</xsl:call-template>
<!-- Tarek Mods
					<xsl:apply-templates select="$names"/>
					<xsl:choose>
						<xsl:when test="$nodetotal!=$position">
							<xsl:text>.</xsl:text>
						</xsl:when>
						<xsl:when test="$pgtype!='author'">
							<xsl:text>.</xsl:text>
						</xsl:when>
					</xsl:choose>
-->
				</xsl:when>

				<xsl:otherwise>
					<xsl:call-template name="initials">
						<xsl:with-param name="name" select="$names"/>
					</xsl:call-template>
				</xsl:otherwise>

			</xsl:choose>
		</xsl:if>

	</xsl:template>
	<!-- strip superfluous . from article-title and source elements -->
<!--	<xsl:template match="text()[parent::article-title or parent::source]">
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
-->

	<!-- ============================================================= -->
	<!-- mode=none                                                     -->
	<!-- ============================================================= -->

	<!-- This mode assumes no punctuation is provided in the XML.
     It is used, among other things, for the citation/ref
     when there is no significant text node inside the ref.        -->

	<xsl:template match="name" mode="none">
		<xsl:value-of select="surname"/>
		<xsl:text>, </xsl:text>
		<xsl:value-of select="given-names"/>
		<xsl:text>. </xsl:text>
	</xsl:template>

	<xsl:template match="article-title" mode="none">
		<xsl:variable name="last-char" select="normalize-space(substring(., string-length(.), 1))"/>
		<xsl:choose>
			<xsl:when test="contains('?', $last-char) or contains('!', $last-char)">
				<xsl:apply-templates/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="volume" mode="none">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="edition" mode="none">
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>

	<xsl:template match="supplement" mode="none">
		<xsl:text> </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="issue" mode="none">
		<xsl:text>(</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>)</xsl:text>

	<xsl:choose>
		<xsl:when test="../fpage"/>
		<xsl:otherwise>
			<xsl:if test="../access-date">
				<xsl:text> (</xsl:text>
				<xsl:value-of select="../access-date"/>
				<xsl:text>) </xsl:text>
			</xsl:if>
		</xsl:otherwise>
	</xsl:choose>
	</xsl:template>

	<xsl:template match="publisher-loc" mode="none">
		<xsl:apply-templates/>
		<xsl:text>: </xsl:text>
	</xsl:template>

	<xsl:template match="publisher-name" mode="none">
		<xsl:apply-templates/>
		<xsl:choose>
			<xsl:when test="../access-date and not(../year)">
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>; </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="person-group" mode="none">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="collab" mode="none">
		<xsl:variable name="nodetotal">
			<xsl:choose>
				<xsl:when test="count(../person-group/*) &lt; 7">
					<xsl:value-of select="count(../person-group/*)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="7" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:if test="$nodetotal &lt; 7">
			<xsl:apply-templates/>
			<xsl:if test="@collab-type">
				<xsl:text>, </xsl:text>
				<xsl:value-of select="@collab-type"/>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="following-sibling::collab">
					<xsl:text>; </xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>. </xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>

	<xsl:template match="collab">
		<xsl:apply-templates/>
		<xsl:if test="@collab-type">
			<xsl:text>, </xsl:text>
			<xsl:value-of select="@collab-type"/>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="following-sibling::collab">
				<xsl:text>; </xsl:text>
			</xsl:when>

			<xsl:otherwise>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="source" mode="none">
		<xsl:text> </xsl:text>
		<i><xsl:apply-templates/></i>

		<xsl:choose>
			<xsl:when test="../volume | ../fpage">
				<xsl:if test="../edition">
					<xsl:text> (</xsl:text>
					<xsl:apply-templates select="../edition" mode="plain"/>
					<xsl:text>)</xsl:text>
				</xsl:if>
				<xsl:text> </xsl:text>
			</xsl:when>

			<xsl:otherwise>
				<xsl:if test="../edition">
					<xsl:text> (</xsl:text>
					<xsl:apply-templates select="../edition" mode="plain"/>
					<xsl:text>)</xsl:text>
				</xsl:if>
				<xsl:choose>
					<xsl:when test="following-sibling::access-date">
						<xsl:text> </xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>. </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="trans-title" mode="none">
		<xsl:text> [</xsl:text>
		<xsl:apply-templates/>
		<xsl:text>]. </xsl:text>
	</xsl:template>

	<xsl:template match="day" mode="none"/>

	<xsl:template match="year" mode="none">
		<xsl:apply-templates mode="none"/>
		<xsl:if test="../volume or ../issue">
			<xsl:text>;</xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template match="season" mode="none">
		<xsl:apply-templates/>
		<xsl:text>;</xsl:text>
	</xsl:template>

	<xsl:template match="fpage" mode="none">
		<xsl:variable name="fpgct" select="count(../fpage)"/>
		<xsl:variable name="lpgct" select="count(../lpage)"/>
		<xsl:variable name="hermano" select="name(following-sibling::node())"/>		
		<xsl:choose>
			<xsl:when test="preceding-sibling::fpage">
				<xsl:choose>
					<xsl:when test="following-sibling::fpage">
						<xsl:text> </xsl:text>
						<xsl:apply-templates/>

						<xsl:if test="$hermano='lpage'">
							<xsl:text>&#8211;</xsl:text>
							<xsl:apply-templates select="following-sibling::lpage[1]" mode="none"/>
						</xsl:if>
						<xsl:text>,</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text> </xsl:text>
						<xsl:apply-templates/>
						<xsl:if test="$hermano='lpage'">
							<xsl:text>&#8211;</xsl:text>
							<xsl:apply-templates select="following-sibling::lpage[1]" mode="none"/>
						</xsl:if>
						<xsl:text>.</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>:</xsl:text>
				<xsl:apply-templates/>
				<xsl:choose>
					<xsl:when test="$hermano='lpage'">
						<xsl:text>&#8211;</xsl:text>
						<xsl:apply-templates select="following-sibling::lpage[1]" mode="write"/>
						<xsl:text>.</xsl:text>
					</xsl:when>
					<xsl:when test="$hermano='fpage'">
						<xsl:text>,</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>.</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="../access-date">
			<xsl:text> (</xsl:text>
			<xsl:value-of select="../access-date"/>
			<xsl:text>) </xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template match="lpage" mode="none"/>
	
	<xsl:template match="lpage" mode="write">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="gov" mode="none">
		<xsl:choose>
			<xsl:when test="../trans-title">
				<xsl:apply-templates/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:apply-templates/>
				<xsl:text>. </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="patent" mode="none">
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>

	<xsl:template match="access-date" mode="none"/>
	
	<!-- special handling for citation links -->
	<xsl:template match="issn" mode="none"/>

	<xsl:template match="issn" mode="nscitation"/>

	<xsl:template match="issn" mode="citation"/>

	<xsl:template match="issn"/>


	<xsl:template match="comment" mode="none">
		<xsl:text> </xsl:text>
		<xsl:apply-templates/>
		<xsl:text>. </xsl:text>
	</xsl:template>


	<xsl:template match="comment/ext-link | uri" mode="none">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="comment/ext-link | uri" mode="nscitation">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="comment/ext-link | uri" mode="citation">
		<xsl:apply-templates select="."/>
	</xsl:template>
	
	<xsl:template match="comment/ext-link">

		<xsl:variable name="href">
			<xsl:value-of select="@xlink:href"/>
		</xsl:variable>

		<xsl:text> [</xsl:text>
		<!-- NB: target is deprecated in XHTML 1.0S -->
		<a>
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

	<xsl:template match="uri">

		<xsl:text> [</xsl:text>
		<!-- NB: target is deprecated in XHTML 1.0S -->
		<a>
			<xsl:call-template name="make-id"/>
			<xsl:attribute name="href">
				<xsl:value-of select="."/>
			</xsl:attribute>
			<xsl:text>Full Text</xsl:text>
		</a>
		<xsl:text>] </xsl:text>

	</xsl:template>


	<!-- ============================================================= -->
	<!--  57. "CITATION-TAG-ENDS"                                      -->
	<!-- ============================================================= -->


	<xsl:template name="citation-tag-ends">

		<xsl:apply-templates select="series" mode="citation"/>

		<!-- If language is not English -->
		<!-- XX review logic -->
		<xsl:if test="article-title[@xml:lang!='en']
               or article-title[@xml:lang!='EN']">

			<xsl:call-template name="language">
				<xsl:with-param name="lang" select="article-title/@xml:lang"/>
			</xsl:call-template>
		</xsl:if>

		<xsl:if test="source[@xml:lang!='en']
              or source[@xml:lang!='EN']">

			<xsl:call-template name="language">
				<xsl:with-param name="lang" select="source/@xml:lang"/>
			</xsl:call-template>
		</xsl:if>

		<xsl:apply-templates select="comment" mode="citation"/>

		<xsl:apply-templates select="annotation" mode="citation"/>

	</xsl:template>


		<!--    template to generate initials from names     -->
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

</xsl:transform>