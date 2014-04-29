{**
 * templates/rt/findingReferences.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- "finding references" page.
 *
 *}
{strip}
{assign var=pageTitle value="rt.findingReferences"}
{include file="rt/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--

function invokeGoogleScholar() {
	var googleScholarForm = document.getElementById('googleScholar');

	googleScholarForm.as_q.value = document.getElementById('inputForm').title.value;
	googleScholarForm.as_sauthors.value = document.getElementById('inputForm').author.value;
	googleScholarForm.submit();
}

function invokeWLA() {
	var wlaForm = document.getElementById('wla');
	wlaForm.q.value = document.getElementById('inputForm').title.value + " " + document.getElementById('inputForm').author.value;
	wlaForm.submit();
}

// -->
{/literal}
</script>

<h3>{$article->getLocalizedTitle()|strip_unsafe_html}</h3>

<!-- Include the real forms for each of the search engines -->
<form id="googleScholar" method="get" action="http://scholar.google.com/scholar">
	<input type="hidden" name="as_q" value="" />
	<input type="hidden" name="as_sauthors" value="" />
	<input type="hidden" name="btnG" value="Search Scholar" />
	<input type="hidden" name="as_occt" value="any" />
	<input type="hidden" name="as_allsubj" value="all" />
</form>

<form id="wla" method="get" action="http://search.live.com/results.aspx">
	<input type="hidden" name="q" value="" />
	<input type="hidden" name="scope" value="academic" />
</form>

<form id="inputForm" target="#">

<!-- Display the form fields -->
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%"><label for="author">{translate key="user.role.author"}</label></td>
		<td class="value" width="80%"><input name="author" id="author" type="text" size="20" maxlength="40" class="textField" value="{$article->getAuthorString()|escape}" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="title">{translate key="article.title"}</label></td>
		<td class="value"><input type="text" id="title" name="title" size="40" maxlength="40" class="textField" value="{$article->getLocalizedTitle()|escape}" /></td>
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

