{**
 * search.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site/journal search form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="navigation.search"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
function ensureKeyword() {
	if (document.search.query.value == '') {
		alert({/literal}'{translate|escape:"javascript" key="search.noKeywordError"}'{literal});
		return false;
	}
	document.search.submit();
	return true;
}
{/literal}
</script>

<form name="search" action="{$pageUrl}/search/results">

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label"><label for="query">{translate key="search.searchFor"}</label></td>
	<td width="80%" class="value"><input type="text" name="query" id="query" size="40" maxlength="255" value="{$query}" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label"><label for="searchField">{translate key="search.inField"}</label></td>
	<td class="value"><select name="searchField" id="searchField" class="selectMenu" >{html_options_translate options=$articleSearchByOptions selected=$searchField}</select></td>
</tr>
{if $siteSearch}
<tr valign="top">
	<td class="label"><label for="searchJournal">{translate key="search.withinJournal"}</label></td>
	<td class="value"><select name="searchJournal" id="searchJournal" class="selectMenu">{html_options options=$journalOptions selected=$searchJournal}</select></td>
</tr>
{/if}
</table>

<p><input type="button" onClick="ensureKeyword();" value="{translate key="common.search"}" class="button defaultButton" /></p>

<br />
&#187 <a href="{$pageUrl}/search/advanced">{translate key="search.advancedSearch"}</a>
<br />
&#187 <a href="{$pageUrl}/search/authors">{translate key="search.browseAuthorIndex"}</a>

<script type="text/javascript">document.search.query.focus();</script>
</form>

{include file="common/footer.tpl"}
