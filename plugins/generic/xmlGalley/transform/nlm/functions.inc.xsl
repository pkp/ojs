<?xml version="1.0"?>

<!--
  * functions.inc.xsl
  *
  * Copyright (c) 2006 MJ Suhonos
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * External XSL functions to support XSL within the XML Galleys plugin.
  *
  * $Id$
  -->

<xsl:transform version="1.0"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        xmlns:mml="http://www.w3.org/1998/Math/MathML"
        xmlns:svg="http://www.w3.org/2000/svg"
        exclude-result-prefixes="xsl xlink mml svg">

	<!--============================================
		FUNCTION TEMPLATES
	==============================================-->

	<!-- set the window name using javascript -->
	<xsl:template name="window_name">
	    <xsl:param name="name"/>

		<xsl:call-template name="nl-1"/>
	    <script type="text/javascript" language="JavaScript">
	        <xsl:text disable-output-escaping="yes">// &lt;![CDATA[ 
			window.name = '</xsl:text>
			<xsl:value-of select="$name"/>
			<xsl:text disable-output-escaping="yes">' ;
			//]]&gt;</xsl:text>
	    </script>

	</xsl:template>

	<!-- generate a javascript-scrambled email link as an anti-sam measure -->
	<xsl:template name="scramble_email">
	    <xsl:param name="email"/>

		<xsl:call-template name="nl-1"/>
	    <script type="text/javascript" language="JavaScript">
	        <xsl:text disable-output-escaping="yes">// &lt;![CDATA[
document.write(String.fromCharCode(60,97,32,104,114,101,102,61,34,109,97,105,108,116,111,58,</xsl:text>

			<xsl:call-template name="to_ascii">
				<xsl:with-param name="str" select="$email"/>
			</xsl:call-template>

		 	<xsl:text>34,62,</xsl:text>

			<xsl:call-template name="to_ascii">
				<xsl:with-param name="str">
					<xsl:value-of select="substring-before($email,'@')"/>
					<xsl:text> [at] </xsl:text>
					<xsl:value-of select="substring-after($email,'@')"/>
				</xsl:with-param>
			</xsl:call-template>

			<xsl:text disable-output-escaping="yes">60,47,97,62));
				//]]&gt;</xsl:text>
	    </script>

		<!-- enable this block if you want to support non-javascript browsers, but it will increase spam -->
		<!--
		<noscript>
			<xsl:value-of select="$email"/>				
		</noscript>
		-->

	</xsl:template>

	<!-- convert a string to valid ascii characters -->
	<xsl:template name="to_ascii">
	    <xsl:param name="str"/>

	    <xsl:if test="$str">
	        <xsl:variable name="ascii-charset"> !&quot;#$%&amp;&apos;()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~&#127;</xsl:variable>
	        <xsl:variable name="first-char" select="substring($str,1,1)"/>
	        <xsl:variable name="ascii-value" select="string-length(substring-before($ascii-charset,$first-char)) + 32"/>
	        <xsl:value-of select="$ascii-value"/><xsl:text>,</xsl:text> 
	        <xsl:call-template name="to_ascii">
	            <xsl:with-param name="str" select="substring($str,2)"/>
	        </xsl:call-template>    
	    </xsl:if>
	</xsl:template>

	<xsl:template name="date-format">
		<!-- date in YYYYMMDD format-->
		<xsl:param name="date"/>

		<!-- string for output format, eg. YYYY-MM-DD -->
		<xsl:param name="format" select="'YYYY MM DD'"/>

		<!-- month format: short/long/number (default) -->
		<xsl:param name="type"/>

		<!-- Month -->
		<xsl:variable name="month">
			<xsl:if test="string(number(substring($date, 5, 2)))!='NaN'">
				<xsl:number format="1" value="substring($date, 5, 2)"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="short_month" select="substring('JanFebMarAprMayJunJulAugSepOctNovDec', $month * 3 - 2, 3)"/>
		<xsl:variable name="long_month">
			<xsl:choose>
				<xsl:when test="$month=1">January</xsl:when>
				<xsl:when test="$month=2">February</xsl:when>
				<xsl:when test="$month=3">March</xsl:when>
				<xsl:when test="$month=4">April</xsl:when>
				<xsl:when test="$month=5">May</xsl:when>
				<xsl:when test="$month=6">June</xsl:when>
				<xsl:when test="$month=7">July</xsl:when>
				<xsl:when test="$month=8">August</xsl:when>
				<xsl:when test="$month=9">September</xsl:when>
				<xsl:when test="$month=10">October</xsl:when>
				<xsl:when test="$month=11">November</xsl:when>
				<xsl:when test="$month=12">December</xsl:when>
				<xsl:otherwise/>  <!-- invalid month -->
			</xsl:choose>
		</xsl:variable>

		<!-- Day -->
		<xsl:variable name="day">
			<xsl:if test="string(number(substring($date, 7, 2)))!='NaN'">
				<xsl:number format="1" value="substring($date, 7, 2)"/>
			</xsl:if>
		</xsl:variable>

		<!-- Year -->
		<xsl:variable name="year" select="substring($date, 1, 4)" />

		<xsl:variable name="result">
			<xsl:call-template name="replace">
				<xsl:with-param name="string" select="$format"/>
				<xsl:with-param name="from" select="'YYYY'"/>
				<xsl:with-param name="to" select="$year"/>
			</xsl:call-template>
		</xsl:variable>

		<xsl:variable name="result2">
			<xsl:choose>
				<xsl:when test="$type='long'">
					<xsl:call-template name="replace">
						<xsl:with-param name="string" select="$result"/>
						<xsl:with-param name="from" select="'MM'"/>
						<xsl:with-param name="to" select="$long_month"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="$type='short'">
					<xsl:call-template name="replace">
						<xsl:with-param name="string" select="$result"/>
						<xsl:with-param name="from" select="'MM'"/>
						<xsl:with-param name="to" select="$short_month"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="replace">
						<xsl:with-param name="string" select="$result"/>
						<xsl:with-param name="from" select="'MM'"/>
						<xsl:with-param name="to" select="$month"/>
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="result3">
			<xsl:call-template name="replace">
				<xsl:with-param name="string" select="$result2"/>
				<xsl:with-param name="from" select="'DD'"/>
				<xsl:with-param name="to" select="$day"/>
			</xsl:call-template>
		</xsl:variable>

		<xsl:value-of select="$result3"/>

	</xsl:template>

	<!-- generic search/replace template -->
	<xsl:template name="replace">
		<xsl:param name="string"/>
		<xsl:param name="from" select="'&#xA;'"/>  <!-- default to newline -->
		<xsl:param name="to"/>

		<xsl:choose>
			<xsl:when test="contains($string, $from)">
				<xsl:copy-of select="substring-before($string, $from)" />
				<xsl:copy-of select="$to" />
				<xsl:call-template name="replace">
					<xsl:with-param name="string" select="substring-after($string, $from)" />
					<xsl:with-param name="from" select="$from" />
					<xsl:with-param name="to" select="$to" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$string" />
			</xsl:otherwise>
		</xsl:choose>
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


		<!--	template for inserting non-width spaces between all characters for URLs	-->
		<!--    usage: call template with parameter "string" to be escaped     -->
		<xsl:template name="spaceURLs">
			<xsl:param name="string"/>
			<xsl:choose>
			    <!--    break at / characters -->
			    <xsl:when test="boolean(substring-before($string, '/'))">
			        <xsl:value-of select="substring-before($string, '/')"/>/&#8203;<xsl:call-template name="spaceURLs"><xsl:with-param name="string" select="substring-after($string, '/')"/></xsl:call-template>
			    </xsl:when>
			    <!--    break at . characters -->
			    <xsl:when test="boolean(substring-before($string, '.'))">
			        <xsl:value-of select="substring-before($string, '.')"/>.&#8203;<xsl:call-template name="spaceURLs"><xsl:with-param name="string" select="substring-after($string, '.')"/></xsl:call-template>
			    </xsl:when>
			    <xsl:otherwise><xsl:value-of select="$string"/></xsl:otherwise>
			</xsl:choose>
		<!--
			<xsl:choose>
				<xsl:when test="substring($string, 1, 1)=' '">&#160;</xsl:when>
				<xsl:otherwise><xsl:value-of select="substring($string, 1, 1)"/>&#8203;</xsl:otherwise>
			</xsl:choose>
			<xsl:if test="string-length($string) > 1">
				<xsl:call-template name="spaceURLs">
				<xsl:with-param name="string" select="substring($string, 2)"/>
				</xsl:call-template>
			</xsl:if>
		-->
		</xsl:template>



		<!--    Escape quotes for javascript     -->
		<!--    usage: call template with parameter "string" to be escaped     -->
		<xsl:template name="fixQuotes">
		   <xsl:param name="string"/>
		       <xsl:choose>
		         <xsl:when test="contains($string, &quot;'&quot;)">
		           <xsl:value-of select="substring-before($string, &quot;'&quot;)" disable-output-escaping="yes"/>
		           <xsl:text>\'</xsl:text>
		           <xsl:call-template name="fixQuotes">
		             <xsl:with-param name="string" select="substring-after($string, &quot;'&quot;)"/>
		           </xsl:call-template>
		         </xsl:when>
		         <xsl:otherwise>
		           <xsl:value-of select="$string" disable-output-escaping="yes"/>
		         </xsl:otherwise>
		       </xsl:choose>
		</xsl:template>


	  <xsl:template name="url-encode">
	    <xsl:param name="str"/>   

	  		<!-- Characters we'll support.
	       		We could add control chars 0-31 and 127-159, but we won't. -->
	  		<xsl:variable name="ascii"> !"#$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~</xsl:variable>
	  		<xsl:variable name="latin1">&#160;&#161;&#162;&#163;&#164;&#165;&#166;&#167;&#168;&#169;&#170;&#171;&#172;&#173;&#174;&#175;&#176;&#177;&#178;&#179;&#180;&#181;&#182;&#183;&#184;&#185;&#186;&#187;&#188;&#189;&#190;&#191;&#192;&#193;&#194;&#195;&#196;&#197;&#198;&#199;&#200;&#201;&#202;&#203;&#204;&#205;&#206;&#207;&#208;&#209;&#210;&#211;&#212;&#213;&#214;&#215;&#216;&#217;&#218;&#219;&#220;&#221;&#222;&#223;&#224;&#225;&#226;&#227;&#228;&#229;&#230;&#231;&#232;&#233;&#234;&#235;&#236;&#237;&#238;&#239;&#240;&#241;&#242;&#243;&#244;&#245;&#246;&#247;&#248;&#249;&#250;&#251;&#252;&#253;&#254;&#255;</xsl:variable>

	  		<!-- Characters that usually don't need to be escaped -->
	  		<xsl:variable name="safe">!'()*-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz~</xsl:variable>
	  		<xsl:variable name="hex" >0123456789ABCDEF</xsl:variable>

	    <xsl:if test="$str">
	      <xsl:variable name="first-char" select="substring($str,1,1)"/>
	      <xsl:choose>
	        <xsl:when test="contains($safe,$first-char)">
	          <xsl:value-of select="$first-char"/>
	        </xsl:when>
	        <xsl:otherwise>
	          <xsl:variable name="codepoint">
	            <xsl:choose>
	              <xsl:when test="contains($ascii,$first-char)">
	                <xsl:value-of select="string-length(substring-before($ascii,$first-char)) + 32"/>
	              </xsl:when>
	              <xsl:when test="contains($latin1,$first-char)">
	                <xsl:value-of select="string-length(substring-before($latin1,$first-char)) + 160"/>
	              </xsl:when>
	              <xsl:otherwise>
	                <xsl:message terminate="no">Warning: string contains a character that is out of range! Substituting "?".</xsl:message>
	                <xsl:text>63</xsl:text>
	              </xsl:otherwise>
	            </xsl:choose>
	          </xsl:variable>
	        <xsl:variable name="hex-digit1" select="substring($hex,floor($codepoint div 16) + 1,1)"/>
	        <xsl:variable name="hex-digit2" select="substring($hex,$codepoint mod 16 + 1,1)"/>
	        <xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)"/>
	        </xsl:otherwise>
	      </xsl:choose>
	      <xsl:if test="string-length($str) &gt; 1">
	        <xsl:call-template name="url-encode">
	          <xsl:with-param name="str" select="substring($str,2)"/>
	        </xsl:call-template>
	      </xsl:if>
	    </xsl:if>
	  </xsl:template>

</xsl:transform>