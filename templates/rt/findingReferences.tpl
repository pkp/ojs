{**
 * bio.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- "finding references" page.
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="rt.findingReferences"}
{include file="rt/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--

function invokeGoogleScholar() {
	document.googleScholar.as_q.value = document.inputForm.title.value;
	document.googleScholar.as_sauthors.value = document.inputForm.author.value;
	document.googleScholar.submit();
}

function invokeWLA() {
	document.wla.q.value = document.inputForm.title.value + " " + document.inputForm.author.value;
	document.wla.submit();
}

// -->
{/literal}
</script>

<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>

<!-- Include the real forms for each of the search engines -->
<form name="googleScholar" method="get" action="http://scholar.google.com/scholar">
	<input type="hidden" name="as_q" value="" />
	<input type="hidden" name="as_sauthors" value="" />
	<input type="hidden" name="btnG" value="Search Scholar" />
	<input type="hidden" name="as_occt" value="any" />
	<input type="hidden" name="as_allsubj" value="all" />
</form>

<form name="wla" method="get" action="http://search.live.com/results.aspx">
	<input type="hidden" name="q" value="" />
	<input type="hidden" name="scope" value="academic" />
</form>

<form name="inputForm" target="#">

<!-- Display the form fields -->
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%"><label for="author">{translate key="user.role.author"}</label></td>
		<td class="value" width="80%"><input name="author" id="author" type="text" size="20" maxlength="40" class="textField" value="{$article->getAuthorString()|escape}" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="title">{translate key="article.title"}</label></td>
		<td class="value"><input type="text" id="title" name="title" size="40" maxlength="40" class="textField" value="{$article->getArticleTitle()|escape}" /></td>
	</tr>
</table>

<!-- Display the search engine options -->
<table class="listing" width="100%">
	<tr valign="top">
		<td width="10%"><input value="{translate key="common.search"}" type="button" onclick="invokeGoogleScholar()" class="button" /></td>
		<td width="2%">1.</td>
		<td width="88%">{translate key="rt.findingReferences.googleScholar"}</td>
	</tr>
	<tr valign="top">
		<td><input value="{translate key="common.search"}" type="button" onclick="invokeWLA()" class="button" /></td>
		<td>2.</td>
		<td>{translate key="rt.findingReferences.windowsLiveAcademic"}</td>
	</tr>
</table>

</form>

{include file="rt/footer.tpl"}
