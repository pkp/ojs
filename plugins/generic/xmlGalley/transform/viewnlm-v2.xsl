<?xml version="1.0"?>
<!-- ============================================================= -->
<!--  MODULE:    HTML View of NLM Journal Article                  -->
<!--  VERSION:   0.2                                               -->
<!--  DATE:      November 2005                                      -->
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
<!--             Publishing DTD.                                   -->
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

 No.  CHANGE (reason for / description)   [who]       VERSION DATE

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

<xsl:transform  version="1.0"
                id="ViewNLM-v2-04.xsl"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:util="http://dtd.nlm.nih.gov/xsl/util"
                xmlns:mml="http://www.w3.org/1998/Math/MathML"
                exclude-result-prefixes="util xsl">

<xsl:output method="html"
            indent="yes"
            encoding="UTF-8"/>
<!--
doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"            doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
-->

<xsl:strip-space elements="abstract ack address annotation app app-group
                           array article article-categories article-meta
                           author-comment author-notes back bio body boxed-text
                           break caption chem-struct chem-struct-wrapper
                           citation col colgroup conference contrib contrib-group
                           copyright-statement date def def-item def-list
                           disp-quote etal fig fig-group fn fn-group front
                           gloss-group glossary glyph-ref graphic history hr
                           inline-graphic journal-meta kwd-group list list-item
                           media mml:math name nlm-citation note notes page-count
                           person-group private-char pub-date publisher ref
                           ref-list response sec speech statement sub-article
                           subj-group supplementary-material table table-wrap
                           table-wrap-foot table-wrap-group tbody tfoot thead
                           title-group tr trans-abstract verse-group
                           "/>

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
<xsl:variable name="person-strings"
  select="document('')/*/util:map[@id='person-strings']/item"/>

<util:map id="person-strings">
  <item source="editor"       singular="editor"
                              plural="editors"/>
  <item source="assignee"     singular="assignee"
                              plural="assignees"/>
  <item source="translator"   singular="translator"
                              plural="translators"/>
  <item source="transed"      singular="translator and editor"
                              plural="translators and editors"/>
  <item source="guest-editor" singular="guest editor"
                              plural="guest editors"/>
  <item source="compiler"     singular="compiler"
                              plural="compilers"/>
  <item source="inventor"     singular="inventor"
                              plural="inventors"/>
  <!-- value 'allauthors' puts no string out -->
</util:map>


<!-- ============================================================= -->
<!--  2. ROOT TEMPLATE - HANDLES HTML FRAMEWORK                    -->
<!-- ============================================================= -->

<!--
<xsl:template match="/">

  <xsl:call-template name="nl-1"/>
  <html>

    <xsl:call-template name="nl-2"/>
    <xsl:call-template name="make-html-header"/>
    <xsl:call-template name="nl-2"/>

    <body bgcolor="#f8f8f8">

    <xsl:apply-templates/>

    </body>

  </html>

</xsl:template>
-->

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

  <!-- figures and tables -->
  <xsl:call-template name="nl-2"/>
  <div id="{$which-piece}-figs-and-tables" class="bm">
    <xsl:call-template name="nl-1"/>
    <xsl:call-template name="make-figs-and-tables"/>
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
  <xsl:value-of select="translate($str,
                          'abcdefghjiklmnopqrstuvwxyz',
                          'ABCDEFGHJIKLMNOPQRSTUVWXYZ')"/>
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
<!--  make-id, make-src, make-href, make-email                     -->
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
    <xsl:attribute name="src">
      <xsl:value-of select="@xlink:href"/>
    </xsl:attribute>
  </xsl:if>
</xsl:template>

<xsl:template name="make-email">
  <xsl:if test="@xlink:href">
    <xsl:attribute name="href">
      <xsl:value-of select="concat('mailto:', @xlink:href)"/>
    </xsl:attribute>
  </xsl:if>
</xsl:template>


<!-- ============================================================= -->
<!--  display-id                                                   -->
<!-- ============================================================= -->

<xsl:template name="display-id">

  <xsl:variable name="display-phrase">
    <xsl:choose>
      <xsl:when test="self::disp-formula"><xsl:text>Formula ID</xsl:text></xsl:when>
      <xsl:when test="self::chem-struct-wrapper"><xsl:text>Chemical Structure Wrapper ID</xsl:text></xsl:when>
      <xsl:when test="self::chem-struct"><xsl:text>Chemical Structure ID</xsl:text></xsl:when>
      <xsl:otherwise><xsl:text>ID</xsl:text></xsl:otherwise>
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
  <tr><td width="30%"/><td/></tr>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<xsl:template name="table-setup-l-narrow">
  <xsl:call-template name="nl-1"/>
  <tr><td width="10%"/><td/></tr>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<xsl:template name="table-setup-even">
  <xsl:call-template name="nl-1"/>
  <tr><td width="50%"/><td/></tr>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- ============================================================= -->
<!-- "make-figs-and-tables"                                        -->
<!-- ============================================================= -->

<!-- initial context node is article -->

<xsl:template name="make-figs-and-tables">

  <xsl:if test="body//fig[not(parent::fig-group)] | back//fig[not(parent::fig-group)]">
    <hr class="section-rule"/>
    <xsl:call-template name="nl-1"/>

    <span class="tl-main-part">Figures</span>
    <xsl:call-template name="nl-1"/>

    <table width="100%" class="bm">
      <xsl:call-template name="table-setup-l-wide"/>

      <!-- each figure is a row -->
      <xsl:apply-templates select="body//fig | back//fig" mode="put-at-end"/>
    </table>

  </xsl:if>

  <xsl:if test="body//table-wrap | back//table-wrap">
    <hr class="section-rule"/>
    <xsl:call-template name="nl-1"/>

    <span class="tl-main-part">Tables</span>
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
          <xsl:value-of select="/article/front/journal-meta/journal-id
                                [@journal-id-type='pubmed']"/>
          <xsl:text>: </xsl:text>
        </xsl:when>

        <xsl:when test="/article/front/journal-meta/journal-id
                       [@journal-id-type='publisher']">
          <xsl:value-of select="/article/front/journal-meta/journal-id
                                [@journal-id-type='publisher']"/>
          <xsl:text>: </xsl:text>
        </xsl:when>

        <xsl:when test="/article/front/journal-meta/journal-id">
          <xsl:value-of select="/article/front/journal-meta/journal-id
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
            <xsl:text>-</xsl:text>
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

    <link rel="stylesheet" type="text/css" href="ViewNLM.css"/>
    <xsl:call-template name="nl-1"/>
  </head>

</xsl:template>


<!-- ============================================================= -->
<!--  8. MAKE-FRONT                                                -->
<!-- ============================================================= -->

<!-- initial context node is /article -->

<xsl:template name="make-front">

  <xsl:call-template name="nl-1"/>

  <!-- First Table: journal and article metadata -->
  <table width="100%" class="fm">
    <xsl:call-template name="nl-1"/>

    <xsl:call-template name="table-setup-even"/>

    <tr>
      <xsl:call-template name="nl-1"/>


      <!-- Cell 1: journal information -->
      <!-- change context node to front/journal-meta -->
      <xsl:for-each select="front/journal-meta">

        <td valign="top">
          <span class="gen"><xsl:text>Journal Information</xsl:text></span>
          <br/>
          <xsl:call-template name="nl-1"/>

          <!-- journal id -->
          <xsl:apply-templates select="journal-id[@journal-id-type]" mode="front"/>

          <!-- abbreviated journal title -->
          <xsl:apply-templates select="abbrev-journal-title" mode="front"/>

          <!-- issn -->
          <xsl:apply-templates select="issn" mode="front"/>

          <!-- publisher -->
          <xsl:apply-templates select="publisher" mode="front"/>

          <!-- journal meta notes -->
          <xsl:apply-templates select="notes" mode="front"/>

        </td>
        <xsl:call-template name="nl-1"/>

      <!-- that's it for journal metadata: return to previous context -->
      </xsl:for-each>


      <!-- Cell 2: Article information -->
      <!-- change context to front/article-meta -->

      <xsl:for-each select="front/article-meta">
        <td valign="top">
          <span class="gen">
            <xsl:text>Article Information</xsl:text>
          </span>
          <br/>
          <xsl:call-template name="nl-1"/>

          <!-- article-level linking -->
          <xsl:apply-templates select="ext-link" mode="front"/>

          <!-- supplementary material -->
          <xsl:apply-templates select="supplementary-material" mode="front"/>

          <!-- self-uri -->
          <xsl:apply-templates select="self-uri" mode="front"/>

          <!-- product information -->
          <xsl:apply-templates select="product" mode="front"/>

          <!-- copyright: show statement -or- year -->
          <!-- Most recent version of DTD recommends using the <permissions> wrapper
               for the copyright data. We handle both cases here. -->
          <xsl:choose>
            <xsl:when test="copyright-statement | permissions/copyright-statement">
              <xsl:apply-templates select="copyright-statement | permissions/copyright-statement" mode="front"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:if test="copyright-year | permissions/copyright-year">
                <span class="gen">
                  <xsl:text>Copyright: </xsl:text>
                </span>
                <xsl:apply-templates select="copyright-year | permissions/copyright-year" mode="front"/>
                <xsl:apply-templates select="copyright-holder | permissions/copyright-holder"/>
              </xsl:if>
            </xsl:otherwise>
          </xsl:choose>
          <br/>

          <!-- history/date -->
          <!-- The history element contains one or more date elements -->
          <xsl:apply-templates select="history/date" mode="front"/>

          <!-- pub-date -->
          <xsl:apply-templates select="pub-date" mode="front"/>

          <!-- other publication data -->
          <xsl:apply-templates select="volume
                                     | issue
                                     | elocation-id
                                     | fpage
                                     | lpage"
                                mode="front"/>

          <xsl:apply-templates select="article-id"/>

          <!-- contract information -->
          <xsl:if test="contract-num | contract-sponsor ">
            <xsl:apply-templates select="contract-num" mode="front"/>
            <xsl:apply-templates select="contract-sponsor" mode="front"/>
          </xsl:if>

        </td>
        <xsl:call-template name="nl-1"/>

      <!-- that's it for article-meta; return to previous context -->
      </xsl:for-each>

    </tr>
    <xsl:call-template name="nl-1"/>

    <!-- part-rule ending this table, separating it from the title-and-authors table -->
    <tr><td colspan="2" valign="top"><hr class="part-rule"/></td></tr>
    <xsl:call-template name="nl-1"/>

  <!-- end of the first table -->
  </table>
  <xsl:call-template name="nl-1"/>


  <!-- New Table: titles and author group -->
  <!-- All data comes from front/article-meta -->

  <table width="100%" class="fm">

    <xsl:call-template name="table-setup-l-wide"/>

    <!-- change context to front/article-meta (again) -->
    <xsl:for-each select="front/article-meta">

      <tr>
        <!-- table 2 row 2: article titles -->
        <td colspan="2" valign="top">
          <xsl:apply-templates select="title-group" mode="front"/>
        </td>
      </tr>
      <xsl:call-template name="nl-1"/>

      <!-- each contrib makes a row: name at left, details at right -->
      <xsl:for-each select="contrib-group/contrib">
        <tr>
          <td valign="top" align="right">
            <xsl:choose>
              <xsl:when test="@xlink:href">
                <a>
                  <xsl:call-template name="make-href"/>
                  <xsl:call-template name="make-id"/>
                  <xsl:apply-templates select="name | collab" mode="front"/>
                </a>
              </xsl:when>
              <xsl:otherwise>
                <span class="capture-id">
                  <xsl:call-template name="make-id"/>
                  <xsl:apply-templates select="name | collab" mode="front"/>
                </span>
              </xsl:otherwise>
            </xsl:choose>
          </td>
          <td valign="top">
            <!-- the name element handles any contrib/xref and contrib/degrees -->
            <xsl:apply-templates select="*[not(self::name)
                                       and not(self::collab)
                                       and not(self::xref)
                                       and not(self::degrees)]"
                                 mode="front"/>
            <xsl:call-template name="nl-1"/>
          </td>
        </tr>
        <xsl:call-template name="nl-1"/>
      </xsl:for-each> <!-- end of contrib -->

      <!-- each aff that is NOT directly inside a contrib
           also makes a row: empty left, details at right -->
      <xsl:for-each select="aff | contrib-group/aff">
        <tr>
           <td/> <!-- empty cell -->
           <td valign="top">
             <xsl:apply-templates select="self::aff" mode="aff-outside-contrib"/>
           </td>
        </tr>
        <xsl:call-template name="nl-1"/>
      </xsl:for-each>


      <!-- author notes -->
      <xsl:if test="author-notes">
        <tr>
          <td/>
          <td valign="top">
            <xsl:apply-templates select="author-notes" mode="front"/>
          </td>
        </tr>
        <xsl:call-template name="nl-1"/>
      </xsl:if>

      <!-- abstract(s) -->

      <xsl:if test="abstract | trans-abstract">

        <!-- rule separates title+authors from abstract(s) -->
        <tr>
          <td colspan="2" valign="top">
            <hr class="section-rule"/>
          </td>
        </tr>
        <xsl:call-template name="nl-1"/>

        <xsl:for-each select="abstract | trans-abstract">

          <!-- title in left column, content (paras, secs) in right -->
          <tr>
            <td valign="top">
              <span class="tl-main-part">
                <!-- if there's no title, create one -->
                <xsl:call-template name="words-for-abstract-title"/>
              </span>
            </td>
            <xsl:call-template name="nl-1"/>

            <td valign="top">
              <xsl:apply-templates select="*[not(self::title)]"/>
            </td>
            <xsl:call-template name="nl-1"/>
          </tr>
          <xsl:call-template name="nl-1"/>

        </xsl:for-each> <!-- end of abstract or trans-abstract -->

      </xsl:if> <!-- end of dealing with abstracts -->

    <!-- end of the titles-and-authors context; return to previous context -->
    </xsl:for-each>

  </table>
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
    <hr class="part-rule"/>
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
  <hr class="part-rule"/>

  <!-- change context node to back -->
  <xsl:for-each select="back">
    <xsl:apply-templates select="title"/>

    <xsl:if test="preceding-sibling::body//fn-group | .//fn-group">
      <span class="tl-main-part">Notes</span>
      <xsl:apply-templates select="preceding-sibling::body//fn-group | .//fn-group"/>
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

    <xsl:if test="article-categories
                | kwd-group
                | related-article
                | conference">

      <hr class="part-rule"/>

      <table width="100%" class="fm">

        <xsl:call-template name="table-setup-l-wide"/>

        <xsl:call-template name="nl-1"/>
        <tr>

          <xsl:call-template name="nl-1"/>
          <td colspan="2" valign="top">

            <!-- hierarchical subjects -->
            <xsl:apply-templates select="article-categories"/>
            <br/>

            <!-- keyword group -->
            <xsl:apply-templates select="kwd-group"/>

            <!-- related article -->
            <xsl:apply-templates select="related-article"/>

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
      <span class="gen"><xsl:text>Article Categories:</xsl:text></span>
  </xsl:if>
  <ul>
    <xsl:apply-templates/>
  </ul>
</xsl:template>

<xsl:template match="subject">
  <li>
    <xsl:apply-templates/>
  </li>
</xsl:template>

<!-- There may be many series-title elements; there
     may be one series-text (description) element. -->
<xsl:template match="series-title">
  <xsl:if test="not(preceding-sibling::series-title)">
    <span class="gen"><xsl:text>Series: </xsl:text></span>
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
          <xsl:text>Related Article(s): </xsl:text>
        </span>
        <xsl:apply-templates/>
      </a>
    </xsl:when>
    <xsl:otherwise>
      <span class="gen">
        <xsl:call-template name="make-id"/>
        <xsl:text>Related Article(s): </xsl:text>
      </span>
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
  <br/>
</xsl:template>

<!-- ============================================================= -->
<!--  Conference                                                   -->
<!-- ============================================================= -->

<xsl:template match="conference">
  <span class="gen"><xsl:text>Conference: </xsl:text></span>
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

<xsl:template match="p">
  <p>
    <xsl:call-template name="make-id"/>
    <xsl:apply-templates/>
  </p>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- The first p in a footnote displays the fn symbol or,
     if no symbol, the fn ID -->
<xsl:template match="fn/p[1]">
  <p>
    <xsl:call-template name="make-id"/>
    <xsl:if test="../@symbol | ../@id">
      <sup>
        <xsl:choose>
          <xsl:when test="../@symbol">
            <xsl:value-of select="../@symbol"/>
          </xsl:when>
          <xsl:when test="../@id">
            <xsl:value-of select="../@id"/>
          </xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
      </sup>
    </xsl:if>

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

  <xsl:if test="position()>'1'">
    <hr class="section-rule"/>
    <xsl:call-template name="nl-1"/>
  </xsl:if>

  <div>
    <xsl:call-template name="make-id"/>
    <xsl:apply-templates/>
  </div>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- no other level of sec puts out a rule -->
<xsl:template match="sec">
  <div>
    <xsl:call-template name="make-id"/>
    <xsl:apply-templates/>
  </div>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- ============================================================= -->
<!--  15. LIST and its Internals                                   -->
<!-- ============================================================= -->

<xsl:template match="list">
  <xsl:call-template name="nl-1"/>

  <xsl:choose>
    <xsl:when test="@list-type='bullet'">
      <xsl:call-template name="nl-1"/>
      <ul>
        <xsl:call-template name="nl-1"/>
        <xsl:apply-templates/>
        <xsl:call-template name="nl-1"/>
      </ul>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="nl-1"/>
        <ol>
          <xsl:call-template name="nl-1"/>
          <xsl:apply-templates/>
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
  <b><xsl:apply-templates/></b>
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
  <p><b><xsl:apply-templates/></b></p>
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

  <table border="4"
         cellpadding="10pt"
         width="100%">
    <xsl:call-template name="make-id"/>

    <!-- the box is achieved by means of a table, and
         tables don't seem to inherit class attributes,
         so we repeat the class attribute here -->

    <xsl:attribute name="class">
      <xsl:choose>
        <xsl:when test="ancestor::front">fm</xsl:when>
        <xsl:when test="ancestor::body">body</xsl:when>
        <xsl:when test="ancestor::back">bm</xsl:when>
        <xsl:otherwise>body</xsl:otherwise>
      </xsl:choose>
    </xsl:attribute>

    <xsl:call-template name="nl-1"/>
    <tr>
      <td valign="top">
        <xsl:apply-templates/>
      </td>
    </tr>

  </table>

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

  <xsl:variable name="the-label">
    <xsl:choose>
      <xsl:when test="label">
        <xsl:value-of select="label"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>Supplementary Material</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <span class="tl-default">
    <xsl:choose>
      <xsl:when test="@xlink:href">
        <a>
          <xsl:call-template name="make-href"/>
          <xsl:call-template name="make-id"/>
          <xsl:value-of select="$the-label"/>
        </a>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="make-id"/>
        <xsl:value-of select="$the-label"/>
      </xsl:otherwise>
    </xsl:choose>
  </span>

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

<xsl:template match="bold">
  <b><xsl:apply-templates/></b>
</xsl:template>

<xsl:template match="italic">
  <i><xsl:apply-templates/></i>
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
  <small><xsl:apply-templates/></small>
</xsl:template>

<xsl:template match="sc//text()">
  <xsl:param name="str" select="."/>

    <xsl:call-template name="capitalize">
      <xsl:with-param name="str" select="$str"/>
    </xsl:call-template>
</xsl:template>

<xsl:template match="strike">
  <s><xsl:apply-templates/></s>
</xsl:template>

<xsl:template match="sub">
  <sub><xsl:apply-templates/></sub>
</xsl:template>

<xsl:template match="sup">
  <sup><xsl:apply-templates/></sup>
</xsl:template>

<xsl:template match="underline">
  <u><xsl:apply-templates/></u>
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
    <xsl:text>[tex-math code here]</xsl:text>
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
    <img>
      <xsl:call-template name="make-src"/>
      <xsl:call-template name="make-id"/>
    </img>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<xsl:template match="media">
    <a>
      <xsl:call-template name="make-href"/>
      <xsl:call-template name="make-id"/>
      <xsl:apply-templates/>
    </a>
  <xsl:call-template name="nl-1"/>
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

<xsl:template match="label | alt-text | attrib | copyright-statement">

    <!-- element-specific handling before content: -->
    <xsl:choose>

      <!-- alt-text gets a generated label-->
      <xsl:when test="self::alt-text">
        <xsl:if test="not(ancestor::fig)
                  and not(ancestor::table)"><br/></xsl:if>

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
      <xsl:when test="self::alt-text | self::long-desc"><br/></xsl:when>

    </xsl:choose>

</xsl:template>


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
<!--  32. FIGURE, MODE PUT-AT-END                                  -->
<!-- ============================================================= -->

<!-- each figure is a row -->
<xsl:template match="fig" mode="put-at-end">

    <!-- left column:  graphic
         right column: captioning elements - label, caption, etc. -->

    <tr>
    <xsl:call-template name="nl-1"/>

      <td valign="top">
        <xsl:apply-templates select="graphic"/>
        <br/>

        <span class="gen">
          <xsl:call-template name="make-id"/>
          <xsl:text>[Figure ID: </xsl:text>
        </span>
        <xsl:value-of select="@id"/>
        <span class="gen">
          <xsl:text>] </xsl:text>
        </span>

      </td>
      <xsl:call-template name="nl-1"/>

      <td valign="top">
        <xsl:apply-templates select="child::*[not(self::graphic)]"/>
      </td>
      <xsl:call-template name="nl-1"/>
    </tr>
    <xsl:call-template name="nl-1"/>

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
    <xsl:if test="@colspan">
      <xsl:attribute name="colspan">
        <xsl:value-of select="@colspan"/>
      </xsl:attribute>
    </xsl:if>
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
    <xsl:if test="@colspan">
      <xsl:attribute name="colspan">
        <xsl:value-of select="@colspan"/>
      </xsl:attribute>
    </xsl:if>
    <xsl:if test="@rowspan">
      <xsl:attribute name="rowspan">
        <xsl:value-of select="@rowspan"/>
      </xsl:attribute>
    </xsl:if>
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
  <span class="gen"><xsl:text>Journal ID (</xsl:text></span>
  <xsl:value-of select="@journal-id-type"/>
  <span class="gen"><xsl:text>): </xsl:text></span>
  <xsl:value-of select="."/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- abbrev-journal-title -->

<xsl:template match="abbrev-journal-title" mode="front">
  <span class="gen"><xsl:text>Journal Abbreviation: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- issn -->

<xsl:template match="issn" mode="front">
  <span class="gen"><xsl:text>ISSN: </xsl:text></span>
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
  <span class="gen"><xsl:text>Publisher: </xsl:text></span>
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
  <span class="gen"><xsl:text>Supplementary Material:</xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- self-uri -->

<xsl:template match="self-uri" mode="front">
  <a href="@xlink:href"><span class="gen"><xsl:text>Self URI: </xsl:text></span></a>
  <xsl:apply-templates/>
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
        <span class="gen"><xsl:text>Product Information: </xsl:text></span>
        <xsl:apply-templates mode="product"/>
      </a>
    </xsl:when>
    <xsl:otherwise>
      <span class="gen"><xsl:text>Product Information: </xsl:text></span>
      <xsl:apply-templates mode="product"/>
    </xsl:otherwise>
  </xsl:choose>
  <br/><br/>
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

  <span class="gen"><xsl:text> publication date: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- volume -->

<xsl:template match="volume" mode="front">
  <span class="gen"><xsl:text>Volume: </xsl:text></span>
  <xsl:apply-templates/>
  <xsl:if test="../issue">
    <xsl:text> </xsl:text>
  </xsl:if>
</xsl:template>

<!-- issue -->

<xsl:template match="issue" mode="front">
  <span class="gen"><xsl:text>Issue: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- elocation-id -->

<xsl:template match="elocation-id" mode="front">
  <span class="gen"><xsl:text>E-location ID: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- fpage, lpage -->

<xsl:template match="fpage" mode="front">
  <span class="gen"><xsl:text>First Page: </xsl:text></span>
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
  <span class="gen"><xsl:text>Last Page: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- article-id -->

<xsl:template match="article-id">
  <xsl:choose>
    <xsl:when test="@pub-id-type='coden'">
      <span class="gen"><xsl:text>Coden: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='doi'">
      <span class="gen"><xsl:text>DOI: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='medline'">
      <span class="gen"><xsl:text>Medline Id: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='pii'">
      <span class="gen"><xsl:text>Publisher Item Identifier: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='pmid'">
      <span class="gen"><xsl:text>PubMed Id: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='publisher-id'">
      <span class="gen"><xsl:text>Publisher Id: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='sici'">
      <span class="gen"><xsl:text>Serial Item and Contribution Identifier: </xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='doaj'">
      <span class="gen"><xsl:text>Directory of Open Access Journals</xsl:text></span>
    </xsl:when>
    <xsl:when test="@pub-id-type='other'">
      <span class="gen"><xsl:text>Article Id: </xsl:text></span>
    </xsl:when>
    <xsl:otherwise>
      <span class="gen"><xsl:text>ID: </xsl:text></span>
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

<!-- ============================================================= -->
<!--  36) TITLE-GROUP                                              -->
<!-- ============================================================= -->

<!-- title-group -->

<!-- Appears only in article-meta -->
<!-- The fn-group, if any, is output in the "back" of the
     HTML page, together with any other fn-group. -->

<xsl:template match="title-group" mode="front">
    <span class="tl-document">
      <xsl:apply-templates select="article-title" mode="front"/>
      <xsl:apply-templates select="subtitle" mode="front"/>
      <xsl:apply-templates select="trans-title | alt-title" mode="front"/>
    </span>
</xsl:template>

<xsl:template match="article-title" mode="front">
  <xsl:apply-templates/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- subtitle runs in with title -->
<xsl:template match="subtitle" mode="front">
  <xsl:text>: </xsl:text>
  <xsl:apply-templates/>
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
      <span class="capture-id">
        <xsl:call-template name="make-id"/>
        <xsl:apply-templates/>
      </span>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>


<!-- name -->
<!-- uses mode="contrib" within -->

<xsl:template match="name" mode="front">
    <xsl:apply-templates select="prefix"      mode="contrib"/>
    <xsl:apply-templates select="given-names" mode="contrib"/>
    <xsl:apply-templates select="surname"     mode="contrib"/>
    <xsl:apply-templates select="suffix"      mode="contrib"/>
    <xsl:apply-templates select="../degrees"  mode="contrib"/>
    <xsl:apply-templates select="../xref"     mode="contrib"/>
</xsl:template>

<xsl:template match="prefix | given-names" mode="contrib">
  <xsl:apply-templates/>
  <xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="surname" mode="contrib">
  <xsl:apply-templates/>
</xsl:template>

<xsl:template match="suffix" mode="contrib">
  <xsl:text>, </xsl:text><xsl:apply-templates/>
</xsl:template>

<xsl:template match="degrees" mode="contrib">
  <xsl:text>, </xsl:text>
  <xsl:apply-templates/>
</xsl:template>

<!-- the formatting is sometimes in the source XML,
     e.g., <sup><italic>a</italic></sup> -->
<xsl:template match="xref[@ref-type='author-notes']" mode="contrib">
  <xsl:choose>
    <xsl:when test="'*'">
      <xsl:apply-templates/>
    </xsl:when>
    <xsl:when test="not(.//italic) and not (.//sup)">
      <sup><i><xsl:apply-templates/></i></sup>
    </xsl:when>
    <xsl:when test="not(.//italic)">
      <i><xsl:apply-templates/></i>
    </xsl:when>
    <xsl:otherwise>
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- the formatting is sometimes in the source XML,
     e.g., <sup><italic>a</italic></sup> -->
<xsl:template match="xref[@ref-type='aff']" mode="contrib">
  <xsl:choose>
    <xsl:when test="'*'">
      <xsl:apply-templates/>
    </xsl:when>
    <xsl:when test="not(.//italic) and not (.//sup)">
      <sup><i><xsl:apply-templates/></i></sup>
    </xsl:when>
    <xsl:when test="not(.//italic)">
      <i><xsl:apply-templates/></i>
    </xsl:when>
    <xsl:otherwise>
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- author-comment -->
<!-- optional title, one-or-more paras -->

<xsl:template match="author-comment | bio" mode="front">

  <xsl:variable name="the-title">
    <xsl:choose>
      <xsl:when test="title">
        <xsl:apply-templates select="title" mode="front"/>
      </xsl:when>
      <xsl:when test="self::author-comment">
        <xsl:text>Author Comment: </xsl:text>
      </xsl:when>
      <xsl:when test="self::bio">
        <xsl:text>Bio: </xsl:text>
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

<xsl:template match="author-comment/title | bio/title" mode="front">
  <xsl:apply-templates/>
</xsl:template>

<!-- author-comment/p and bio/p in HTML give too much vertical
     space for the display situation; so we force them to produce
     only breaks. -->
<xsl:template match="author-comment/p | bio/p" mode="front">
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- parts of contrib: address -->

<xsl:template match="address" mode="front">
  <span class="gen">
    <xsl:call-template name="make-id"/>
    <xsl:text>Address: </xsl:text>
  </span>
  <xsl:apply-templates mode="front"/>
  <br/>
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
      <span class="capture-id">
        <xsl:call-template name="make-id"/>
        <xsl:apply-templates/>
      </span>
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
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>



<!-- on-behalf-of -->

<xsl:template match="on-behalf-of" mode="front">
  <span class="gen"><xsl:text>On behalf of: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- role -->

<xsl:template match="role" mode="front">
  <span class="gen"><xsl:text>Role: </xsl:text></span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- email -->

<xsl:template match="email" mode="front">
  <xsl:choose>
    <xsl:when test="@xlink:href">
      <a>
        <xsl:call-template name="make-href"/>
        <span class="gen"><xsl:text>Email: </xsl:text></span>
        <xsl:apply-templates/>
      </a>
    </xsl:when>
    <xsl:otherwise>
      <span class="gen"><xsl:text>Email: </xsl:text></span>
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- author-notes -->

<xsl:template match="author-notes" mode="front">
  <span class="capture-id">
    <xsl:call-template name="make-id"/>
    <xsl:apply-templates mode="front"/>
  </span>
</xsl:template>

<!-- author-notes/title -->

<xsl:template match="author-notes/title" mode="front">
  <b><xsl:apply-templates/></b>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- author-notes/corresp -->
<!-- mixed-content; process it as given -->

<xsl:template match="author-notes/corresp" mode="front">
  <span class="gen">
    <xsl:call-template name="make-id"/>
    <xsl:text>Correspondence: </xsl:text>
  </span>
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- author-notes/fn -->
<!-- optional label, one or more paras -->
<!-- unmoded (author-notes only appears in article-meta) -->

<xsl:template match="author-notes/fn" mode="front">
  <xsl:apply-templates/>
  <br/>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- author-notes/fn/label -->

<xsl:template match="author-notes/fn/label">
  <xsl:apply-templates/>
</xsl:template>

<!-- author-notes/fn/p[1] -->

<xsl:template match="author-notes/fn/p[1]" priority="2">

  <span class="capture-id">
    <xsl:call-template name="make-id"/>

    <xsl:choose>
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
      <xsl:when test="parent::fn/@symbol">
        <sup>
          <xsl:value-of select="parent::fn/@symbol"/>
        </sup>
        <xsl:text> </xsl:text>
      </xsl:when>
      <xsl:when test="@fn-type">
        <xsl:text>[</xsl:text>
        <xsl:value-of select="@fn-type"/>
        <xsl:text>]</xsl:text>
        <xsl:text> </xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <span class="gen">
          <xsl:text>*</xsl:text>
        </span>
        <xsl:text> </xsl:text>
      </xsl:otherwise>
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
      <span class="tl-main-part">Acknowledgments</span>
      <xsl:call-template name="nl-1"/>
    </xsl:if>

    <xsl:apply-templates/>

  </div>

</xsl:template>

<!-- ============================================================= -->
<!--  39. BACK-MATTER: APPENDIX                                    -->
<!-- ============================================================= -->

<xsl:template match="app">

  <xsl:text>&#xA;</xsl:text>
  <xsl:if test="position()>1">
    <hr class="section-rule"/>
  </xsl:if>
  <xsl:call-template name="nl-1"/>

  <div class="capture-id">
    <xsl:call-template name="make-id"/>
    <xsl:apply-templates/>
    <xsl:call-template name="nl-1"/>
  </div>

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
  <div id="{@id}">
    <xsl:apply-templates/>
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
    <small><xsl:apply-templates/></small>
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

  <xsl:apply-templates select="title"/>
  <xsl:call-template name="nl-1"/>

  <table width="100%" cellpadding="2" class="bm">
    <xsl:call-template name="nl-1"/>

    <xsl:call-template name="table-setup-l-wide"/>

    <xsl:if test="term-head|def-head">
      <tr>
        <td valign="top" align="right"><i><xsl:apply-templates select="term-head"/></i></td>
        <td valign="top"><i><xsl:apply-templates select="def-head"/></i></td>
      </tr>
      <xsl:call-template name="nl-1"/>
    </xsl:if>

    <xsl:apply-templates select="def-item"/>

  <xsl:call-template name="nl-1"/>
  </table>
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
    <b><xsl:apply-templates/></b>
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

<xsl:template match="xref[@ref-type='fn']
                  | xref[@ref-type='table-fn']
                  | xref[@ref-type='bibr']">
  <span class="xref">
    <xsl:call-template name="make-id"/>
    <sup>
      <!-- if immediately-preceding sibling was an xref, punctuate
           (otherwise assume desired punctuation is in the source).-->
      <xsl:if test="local-name(preceding-sibling::node()[1])='xref'">
        <span class="gen"><xsl:text>, </xsl:text></span>
      </xsl:if>
      <a target="xrefwindow" href="#{@rid}">
        <xsl:value-of select="@rid"/>
      </a>
    </sup>
  </span>
</xsl:template>

<xsl:template match="text()[normalize-space(.)='-']">
  <xsl:choose>
    <!-- if a hyphen is the only thing in a text node
         and it's between two xrefs, we conclude that
         it's expressing a range, and we superscript it -->
    <xsl:when test="local-name(following-sibling::node()[1])='xref'
                and local-name(preceding-sibling::node()[1])='xref'">
      <sup>-</sup>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>-</xsl:text>
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
    <a target="xrefwindow" href="#{@rid}">
      <xsl:value-of select="@rid"/>
    </a>
  </span>
</xsl:template>

<!-- default: if none of the above ref-types -->
<xsl:template match="xref">
  <span class="xref">
    <xsl:call-template name="make-id"/>
    <a target="xrefwindow" href="#{@rid}">
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

<xsl:template match="abstract/title | body/sec/title
                   | back/title | app-group/title | app/title
                   | glossary/title | def-list/title | ack/title
                   | ref-list/title | back/notes/title">
  <xsl:call-template name="nl-1"/>
  <span class="tl-main-part">
    <xsl:apply-templates/>
  </span>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- ============================================================= -->
<!--  47. TITLES: FIRST-LEVEL DIVISIONS AND DEFAULT                -->
<!-- ============================================================= -->

<!-- first-level divisions and default -->

<xsl:template match="body/sec/sec/title | ack/sec/title | app/sec/title
                   | boxed-text/title | gloss-group/title | notes/sec/title">
  <xsl:call-template name="nl-1"/>
  <span class="tl-lowest-section"><xsl:apply-templates/></span>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- default: any other titles found -->

<xsl:template match="title">
  <xsl:call-template name="nl-1"/>
  <span class="tl-default"><xsl:apply-templates/></span>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- ============================================================= -->
<!--  48. TITLES: MAKE-ABSTRACT-TITLE                              -->
<!-- ============================================================= -->

<!-- called by template for abstract and trans-abstract -->

<xsl:template name="words-for-abstract-title">

  <xsl:choose>
    <!-- if there's a title, use it -->
    <xsl:when test="title">
      <xsl:apply-templates select="title"/>
    </xsl:when>

    <!-- abstract with no title -->
    <xsl:when test="self::abstract">
      <span class="gen"><xsl:text>Abstract</xsl:text></span>
    </xsl:when>

    <!-- trans-abstract with no title -->
    <xsl:when test="self::trans-abstract">
      <span class="gen"><xsl:text>Abstract, Translated</xsl:text></span>
    </xsl:when>
    <!-- there is no logical otherwise -->
  </xsl:choose>

</xsl:template>


<!-- ============================================================= -->
<!--  49. UNMODED DATA ELEMENTS: MISCELLANEOUS                     -->
<!-- ============================================================= -->

<!-- epage -->

<xsl:template match="epage">
  <span class="gen"><xsl:text>Electronic Page: </xsl:text></span>
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
  <p><xsl:apply-templates/></p>
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
  <span class="gen">
    <xsl:text>Year: </xsl:text>
  </span>
  <xsl:apply-templates/>
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
  <xsl:variable name="nodetotal" select="count(../*)"/>
  <xsl:variable name="position" select="position()"/>

  <xsl:choose>
    <xsl:when test="given-names">
      <xsl:apply-templates select="surname"/>
      <xsl:text> </xsl:text>
      <xsl:apply-templates select="given-names"/>
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
    <xsl:otherwise>
      <xsl:choose>
        <xsl:when test="$nodetotal=$position">
          <xsl:choose>
            <xsl:when test="parent::person-group/@person-group-type">
              <xsl:choose>
                <xsl:when test="parent::person-group/@person-group-type='author'">
                  <xsl:text>. </xsl:text>
                </xsl:when>
                <xsl:otherwise/>
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
  <xsl:text>et al.</xsl:text>
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

    <xsl:choose>
      <xsl:when test="ref/label">
        <xsl:call-template name="table-setup-l-wide"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="table-setup-l-narrow"/>
      </xsl:otherwise>
    </xsl:choose>

    <xsl:apply-templates/>

  </table>
  <xsl:call-template name="nl-1"/>
</xsl:template>


<!-- suppress the ref-list title so it doesn't reappear -->
<xsl:template match="ref-list/title" mode="nscitation"/>



<!-- ============================================================= -->
<!--  53. REF                                                      -->
<!-- ============================================================= -->

<!-- each ref is a table row -->
<xsl:template match="ref">
  <tr>
    <xsl:call-template name="nl-1"/>

    <td id="{@id}" valign="top" align="right">
      <xsl:if test="not(label)">
        <xsl:value-of select="@id"/>
      </xsl:if>
      <xsl:apply-templates select="label"/>
    </td>
    <xsl:call-template name="nl-1"/>

    <td valign="top">
      <xsl:apply-templates select="citation|nlm-citation"/>
    </td>
    <xsl:call-template name="nl-1"/>
  </tr>
  <xsl:call-template name="nl-1"/>
</xsl:template>

<!-- becomes content of table cell, column 1-->
<xsl:template match="ref/label">
    <b><i><xsl:apply-templates/><xsl:text>. </xsl:text></i></b>
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

      <!-- if have only element content, presume that
           punctuation not supplied = generate it. -->
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
<xsl:template match="ref/nlm-citation[@citation-type='book']
                   | ref/nlm-citation[@citation-type='thesis']">

  <xsl:variable name="augroupcount" select="count(person-group) + count(collab)"/>

  <xsl:choose>

    <xsl:when test="$augroupcount>1 and
                    person-group[@person-group-type!='author'] and
                    article-title ">
      <xsl:apply-templates select="person-group[@person-group-type='author']" mode="book"/>
      <xsl:apply-templates select="collab" mode="book"/>
      <xsl:apply-templates select="article-title" mode="editedbook"/>
      <xsl:text>In: </xsl:text>
      <xsl:apply-templates select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='allauthors']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
                           mode="book"/>
      <xsl:apply-templates select="source"
                           mode="book"/>
      <xsl:apply-templates select="edition"
                           mode="book"/>
      <xsl:apply-templates select="volume"
                           mode="book"/>
      <xsl:apply-templates select="trans-source"
                           mode="book"/>
      <xsl:apply-templates select="publisher-name | publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="fpage | lpage"
                           mode="book"/>
    </xsl:when>

    <xsl:when test="person-group[@person-group-type='author'] or
                    person-group[@person-group-type='compiler']">
      <xsl:apply-templates select="person-group[@person-group-type='author']
                                 | person-group[@person-group-type='compiler']"
                           mode="book"/>
      <xsl:apply-templates select="collab"
                           mode="book"/>
      <xsl:apply-templates select="source"
                           mode="book"/>
      <xsl:apply-templates select="edition"
                           mode="book"/>
      <xsl:apply-templates select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
                           mode="book"/>
      <xsl:apply-templates select="volume"
                           mode="book"/>
      <xsl:apply-templates select="trans-source"
                           mode="book"/>
      <xsl:apply-templates select="publisher-name | publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="article-title | fpage | lpage"
                           mode="book"/>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed']
                                 | person-group[@person-group-type='guest-editor']"
                           mode="book"/>
      <xsl:apply-templates select="collab"
                           mode="book"/>
      <xsl:apply-templates select="source"
                           mode="book"/>
      <xsl:apply-templates select="edition"
                           mode="book"/>
      <xsl:apply-templates select="volume"
                           mode="book"/>
      <xsl:apply-templates select="trans-source"
                           mode="book"/>
      <xsl:apply-templates select="publisher-name | publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="article-title | fpage | lpage"
                           mode="book"/>
    </xsl:otherwise>
  </xsl:choose>

  <xsl:call-template name="citation-tag-ends"/>
</xsl:template>


<!-- Conference proceedings -->
<xsl:template match="ref/nlm-citation[@citation-type='confproc']">

  <xsl:variable name="augroupcount" select="count(person-group) + count(collab)"/>

  <xsl:choose>
    <xsl:when test="$augroupcount>1 and person-group[@person-group-type!='author']">
      <xsl:apply-templates select="person-group[@person-group-type='author']"
                           mode="book"/>
      <xsl:apply-templates select="collab"/>
      <xsl:apply-templates select="article-title"
                           mode="inconf"/>
      <xsl:text>In: </xsl:text>
      <xsl:apply-templates select="person-group[@person-group-type='editor']
                                 | person-group[@person-group-type='allauthors']
                                 | person-group[@person-group-type='translator']
                                 | person-group[@person-group-type='transed'] "
                           mode="book"/>
      <xsl:apply-templates select="source"
                           mode="conf"/>
      <xsl:apply-templates select="conf-name | conf-date | conf-loc"
                           mode="conf"/>
      <xsl:apply-templates select="publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="publisher-name"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="fpage | lpage"
                           mode="book"/>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates select="person-group"
                           mode="book"/>
      <xsl:apply-templates select="collab"
                           mode="book"/>
      <xsl:apply-templates select="article-title"
                           mode="conf"/>
      <xsl:apply-templates select="source" mode="conf"/>
      <xsl:apply-templates select="conf-name | conf-date | conf-loc"
                           mode="conf"/>
      <xsl:apply-templates select="publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="publisher-name"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="fpage | lpage"
                           mode="book"/>
    </xsl:otherwise>
  </xsl:choose>

  <xsl:call-template name="citation-tag-ends"/>
</xsl:template>


<!-- Government and other reports, other, web, and commun -->
<xsl:template match="ref/nlm-citation[@citation-type='gov']
                   | ref/nlm-citation[@citation-type='web']
                   | ref/nlm-citation[@citation-type='commun']
                   | ref/nlm-citation[@citation-type='other']">

  <xsl:apply-templates select="person-group" mode="book"/>

  <xsl:apply-templates select="collab"/>

  <xsl:choose>
    <xsl:when test="publisher-loc | publisher-name">
      <xsl:apply-templates select="source"
                           mode="book"/>
      <xsl:choose>
        <xsl:when test="@citation-type='web'">
          <xsl:apply-templates select="edition"
                               mode="none"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates select="edition"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:apply-templates select="publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="publisher-name"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
      <xsl:apply-templates select="article-title|gov"
                           mode="none"/>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates select="article-title|gov"
                           mode="book"/>
      <xsl:apply-templates select="source"
                           mode="book"/>
      <xsl:apply-templates select="edition"/>
      <xsl:apply-templates select="publisher-loc"
                           mode="none"/>
      <xsl:apply-templates select="publisher-name"
                           mode="none"/>
      <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                           mode="book"/>
    </xsl:otherwise>
  </xsl:choose>

  <xsl:apply-templates select="fpage | lpage"
                       mode="book"/>

  <xsl:call-template name="citation-tag-ends"/>

</xsl:template>


<!-- Patents  -->
<xsl:template match="ref/nlm-citation[@citation-type='patent']">

  <xsl:apply-templates select="person-group"
                       mode="book"/>
  <xsl:apply-templates select="collab"
                       mode="book"/>
  <xsl:apply-templates select="article-title | trans-title"
                       mode="none"/>
  <xsl:apply-templates select="source"
                       mode="none"/>
  <xsl:apply-templates select="patent"
                       mode="none"/>
  <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                       mode="book"/>
  <xsl:apply-templates select="fpage | lpage"
                       mode="book"/>

  <xsl:call-template name="citation-tag-ends"/>

</xsl:template>


<!-- Discussion  -->
<xsl:template match="ref/nlm-citation[@citation-type='discussion']">

  <xsl:apply-templates select="person-group"
                       mode="book"/>
  <xsl:apply-templates select="collab"/>
  <xsl:apply-templates select="article-title"
                       mode="editedbook"/>
  <xsl:text>In: </xsl:text>
  <xsl:apply-templates select="source"
                       mode="none"/>

  <xsl:if test="publisher-name | publisher-loc">
    <xsl:text> [</xsl:text>
    <xsl:apply-templates select="publisher-loc"
                         mode="none"/>
    <xsl:value-of select="publisher-name"/>
    <xsl:text>]; </xsl:text>
  </xsl:if>

  <xsl:apply-templates select="year | month | time-stamp | season | access-date"
                       mode="book"/>
  <xsl:apply-templates select="fpage | lpage"
                       mode="book"/>

  <xsl:call-template name="citation-tag-ends"/>
</xsl:template>


<!-- If none of the above citation-types applies,
     use mode="none". This generates punctuation. -->
<!-- (e.g., citation-type="journal"              -->
<xsl:template match="nlm-citation">

  <xsl:apply-templates select="*[not(self::annotation) and
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



<!-- if given names aren't all-caps, use book mode -->

<xsl:template name="make-persons-in-mode">

  <xsl:variable name="gnms"
    select="string(descendant::given-names)"/>

  <xsl:variable name="GNMS"
    select="translate($gnms,
      'abcdefghjiklmnopqrstuvwxyz',
      'ABCDEFGHJIKLMNOPQRSTUVWXYZ')"/>

      <xsl:choose>
        <xsl:when test="$gnms=$GNMS">
          <xsl:apply-templates/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates mode="book"/>
        </xsl:otherwise>
      </xsl:choose>

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
          <xsl:text>, </xsl:text>
          <xsl:value-of select="($person-strings[@source=$person-group-type]/@plural)"/>
        </xsl:when>

        <xsl:otherwise>
          <xsl:text>, </xsl:text>
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

  <!-- the usual choice pattern: semi-colon or period? -->
        <xsl:when test="following-sibling::person-group">
          <xsl:text>; </xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>. </xsl:text>
        </xsl:otherwise>
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
        <xsl:text>, </xsl:text>
        <xsl:call-template name="firstnames" >
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
                      <xsl:otherwise>; </xsl:otherwise>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>; </xsl:otherwise>
                </xsl:choose>
              </xsl:when>

              <!-- if not author -->
              <xsl:otherwise>
                <xsl:choose>
                  <xsl:when test="$nodetotal=$position"/>
                  <xsl:when test="$penult=$position">
                    <xsl:choose>
                      <xsl:when test="following-sibling::etal">, </xsl:when>
                      <xsl:otherwise>; </xsl:otherwise>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>; </xsl:otherwise>
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
                    <xsl:otherwise>; </xsl:otherwise>
                                    </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>; </xsl:otherwise>
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
  <xsl:text>et al.</xsl:text>
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
  <xsl:apply-templates/>
  <xsl:text>. </xsl:text>
</xsl:template>

<xsl:template match="article-title" mode="book">
  <xsl:apply-templates/>

  <xsl:choose>
    <xsl:when test="../fpage or ../lpage">
      <xsl:text>; </xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>. </xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="article-title" mode="editedbook">
  <xsl:apply-templates/>
  <xsl:text>. </xsl:text>
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
  <i><xsl:apply-templates/></i>
</xsl:template>

<xsl:template match="source" mode="book">
  <xsl:choose>

    <xsl:when test="../trans-source">
      <xsl:apply-templates/>
      <xsl:choose>
        <xsl:when test="../volume | ../edition">
          <xsl:text>. </xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text> </xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates/>
      <xsl:text>. </xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="source" mode="conf">
  <xsl:apply-templates/>
  <xsl:text>; </xsl:text>
</xsl:template>

<xsl:template match="trans-source" mode="book">
  <xsl:text> [</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>]. </xsl:text>
</xsl:template>

<xsl:template match="volume" mode="nscitation">
  <xsl:text> </xsl:text><xsl:apply-templates/>
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

<xsl:template match="month" mode="nscitation">
  <xsl:apply-templates/><xsl:text>.</xsl:text>
</xsl:template>

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
    <xsl:when test="$month='09' or $month='9' or $month='September'">Sep</xsl:when>
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
  <xsl:apply-templates/><xsl:text>. </xsl:text>
</xsl:template>


<xsl:template match="year" mode="nscitation">
  <xsl:text> </xsl:text>
  <xsl:apply-templates/>
  <xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="year" mode="book">
  <xsl:choose>
    <xsl:when test="../month or ../season or ../access-date">
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
  <xsl:text>. </xsl:text>
</xsl:template>

<xsl:template match="time-stamp" mode="book"/>


<xsl:template match="access-date" mode="nscitation">
  <xsl:apply-templates/>
  <xsl:text>. </xsl:text>
</xsl:template>

<xsl:template match="access-date" mode="book">
  <xsl:text> [</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>]. </xsl:text>
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
  <xsl:if test="../lpage">
    <xsl:text>-</xsl:text>
    <xsl:value-of select="../lpage"/>
  </xsl:if>
  <xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="fpage" mode="book">
  <xsl:text>p. </xsl:text>
  <xsl:apply-templates/>

  <xsl:if test="../lpage">
    <xsl:text>.</xsl:text>
  </xsl:if>

</xsl:template>


<xsl:template match="lpage" mode="book">
  <xsl:choose>
    <xsl:when test="../fpage">
      <xsl:text>-</xsl:text>
      <xsl:apply-templates/>
      <xsl:text>.</xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:apply-templates/>
      <xsl:text> p.</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="lpage" mode="nscitation"/>

<!-- misc stuff -->

<xsl:template match="pub-id[@pub-id-type='pmid']" mode="nscitation">
  <xsl:variable name="pmid" select="."/>
  <xsl:variable name="href" select="'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;dopt=abstract&amp;list_uids='"/>
  <xsl:text> [</xsl:text>
  <a>
    <xsl:attribute name="href">
      <xsl:value-of select="concat($href,$pmid)"/>
    </xsl:attribute>
    <xsl:attribute name="target">
      <xsl:text>_new</xsl:text>
    </xsl:attribute>PubMed
  </a>
  <xsl:text>]</xsl:text>
</xsl:template>

<xsl:template match="annotation" mode="nscitation">
  <blockquote><xsl:apply-templates/></blockquote>
</xsl:template>

<xsl:template match="comment" mode="nscitation">
  <xsl:if test="not(self::node()='.')">
    <br/>
    <small><xsl:apply-templates/></small>
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


<!-- ============================================================= -->
<!--  "firstnames"                                                 -->
<!-- ============================================================= -->

<!-- called by match="name" in book mode,
     as part of citation handling
     when given-names is not all-caps -->

<xsl:template name="firstnames" >
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
        <xsl:apply-templates select="$names"/>
        <xsl:choose>
          <xsl:when test="$nodetotal!=$position">
            <xsl:text>.</xsl:text>
          </xsl:when>
          <xsl:when test="$pgtype!='author'">
            <xsl:text>.</xsl:text>
          </xsl:when>
        </xsl:choose>
      </xsl:when>

      <xsl:otherwise>
        <xsl:apply-templates select="$names"/>
      </xsl:otherwise>

    </xsl:choose>
  </xsl:if>

</xsl:template>



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
  <xsl:apply-templates/>
  <xsl:if test="../trans-title">
    <xsl:text>. </xsl:text>
  </xsl:if>
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
</xsl:template>

<xsl:template match="publisher-loc" mode="none">
  <xsl:apply-templates/>
  <xsl:text>: </xsl:text>
</xsl:template>

<xsl:template match="publisher-name" mode="none">
  <xsl:apply-templates/>
  <xsl:text>; </xsl:text>
</xsl:template>

<xsl:template match="person-group" mode="none">
  <xsl:variable name="gnms" select="string(descendant::given-names)"/>
  <xsl:variable name="GNMS">
    <xsl:call-template name="capitalize">
      <xsl:with-param name="str" select="$gnms"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:choose>
    <xsl:when test="$gnms=$GNMS">
      <xsl:apply-templates/>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates select="node()" mode="book"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="collab" mode="none">
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
  <xsl:apply-templates/>

  <xsl:choose>
    <xsl:when test="../access-date">
      <xsl:if test="../edition">
        <xsl:text> (</xsl:text>
        <xsl:apply-templates select="../edition" mode="plain"/>
        <xsl:text>)</xsl:text>
      </xsl:if>
      <xsl:text>. </xsl:text>
    </xsl:when>

    <xsl:when test="../volume | ../fpage">
      <xsl:if test="../edition">
        <xsl:text> (</xsl:text><xsl:apply-templates select="../edition" mode="plain"/><xsl:text>)</xsl:text>
      </xsl:if>
      <xsl:text> </xsl:text>
    </xsl:when>

    <xsl:otherwise>
      <xsl:if test="../edition">
        <xsl:text> (</xsl:text>
        <xsl:apply-templates select="../edition" mode="plain"/>
        <xsl:text>)</xsl:text>
      </xsl:if>
      <xsl:text>. </xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="trans-title" mode="none">
  <xsl:text> [</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>]. </xsl:text>
</xsl:template>

<xsl:template match="month" mode="none">
 <xsl:variable name="month" select="."/>
  <xsl:choose>
    <xsl:when test="$month='01' or $month='1' ">Jan</xsl:when>
    <xsl:when test="$month='02' or $month='2' ">Feb</xsl:when>
    <xsl:when test="$month='03' or $month='3' ">Mar</xsl:when>
    <xsl:when test="$month='04' or $month='4' ">Apr</xsl:when>
    <xsl:when test="$month='05' or $month='5' ">May</xsl:when>
    <xsl:when test="$month='06' or $month='6'">Jun</xsl:when>
    <xsl:when test="$month='07' or $month='7'">Jul</xsl:when>
    <xsl:when test="$month='08' or $month='8' ">Aug</xsl:when>
    <xsl:when test="$month='09' or $month='9' ">Sep</xsl:when>
    <xsl:when test="$month='10' ">Oct</xsl:when>
    <xsl:when test="$month='11' ">Nov</xsl:when>
    <xsl:when test="$month='12' ">Dec</xsl:when>

    <xsl:otherwise>
      <xsl:value-of select="$month"/>
    </xsl:otherwise>
  </xsl:choose>

  <xsl:if test="../day">
    <xsl:text> </xsl:text>
    <xsl:value-of select="../day"/>
  </xsl:if>

  <xsl:text>;</xsl:text>

</xsl:template>

<xsl:template match="day" mode="none"/>

<xsl:template match="year" mode="none">
  <xsl:choose>
    <xsl:when test="../month or ../season or ../access-date">
      <xsl:apply-templates mode="none"/>
      <xsl:text> </xsl:text>
    </xsl:when>

    <xsl:otherwise>
      <xsl:apply-templates mode="none"/>
      <xsl:if test="../volume or ../issue">
        <xsl:text>;</xsl:text>
      </xsl:if>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="access-date" mode="none">
  <xsl:text> [</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>];</xsl:text>
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
          <xsl:apply-templates select="following-sibling::lpage[1]" mode="none"/>
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

</xsl:template>

<xsl:template match="lpage" mode="none">
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





</xsl:transform>
