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

{assign var="pageTitle" value="common.search"}
{assign var="pageId" value="search.search"}
{include file="common/header.tpl"}

<form name="search" action="{$pageUrl}/search/results" method="post">

<div class="form">
<table class="form">
<tr>
	<td class="formLabel">{translate key="search.searchFor"}:</td>
	<td class="formField"><input type="text" name="query" size="40" maxlength="255" value="{$query}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.inField"}:</td>
	<td class="formField"><select name="searchField">{html_options_translate options=$searchFieldOptions selected=$searchField}</select></td>
</tr>
{if $siteSearch}
<tr>
	<td class="formLabel">{translate key="search.withinJournal"}:</td>
	<td class="formField"><select name="searchJournal">{html_options options=$journalOptions selected=$searchJournal}</select></td>
</tr>
{/if}
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.search"}" class="formButton" /></td>
</tr>
</table>

<br />
&#187 <a href="{$pageUrl}/search/advanced">{translate key="search.advancedSearch"}</a>
<br />
&#187 <a href="{$pageUrl}/search/authors">{translate key="search.browseAuthorIndex"}</a>
</div>

<script type="text/javascript">document.search.query.focus();</script>
</form>

{include file="common/footer.tpl"}
