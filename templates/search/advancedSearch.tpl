{**
 * advancedSearch.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site/journal advanced search form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="search.advancedSearch"}
{include file="common/header.tpl"}

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form name="search" action="{$pageUrl}/search/advancedResults" method="post">

<div class="form">
<table class="form">
<tr>
	<td class="formLabel">{translate key="search.searchAllCategories"}:</td>
	<td class="formField"><input type="text" name="query" size="40" maxlength="255" value="{$query}" class="textField" /></td>
</tr>
{if $siteSearch}
<tr>
	<td class="formLabel">{translate key="search.withinJournal"}:</td>
	<td class="formField"><select name="searchJournal">{html_options options=$journalOptions selected=$searchJournal}</select></td>
</tr>
{/if}
<tr>
	<td class="formSubLabel">{translate key="search.searchCategories"}</td>
	<td></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.author"}:</td>
	<td class="formField"><input type="text" name="author" size="40" maxlength="255" value="{$author}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.title"}:</td>
	<td class="formField"><input type="text" name="title" size="40" maxlength="255" value="{$title}" class="textField" /></td>
</tr>
<tr>
	<td class="formSubLabel">{translate key="search.date"}</td>
	<td></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.dateFrom"}:</td>
	<td class="formField">{html_select_date prefix="dateFrom" time=$dateFrom all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.dateTo"}:</td>
	<td class="formField">{html_select_date prefix="dateTo" time=$dateTo all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
</tr>
<tr>
	<td class="formSubLabel">{translate key="search.indexTerms"}</td>
	<td></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.discipline"}:</td>
	<td class="formField"><input type="text" name="discipline" size="40" maxlength="255" value="{$discipline}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.subject"}:</td>
	<td class="formField"><input type="text" name="subject" size="40" maxlength="255" value="{$subject}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.typeMethodApproach"}:</td>
	<td class="formField"><input type="text" name="type" size="40" maxlength="255" value="{$type}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="search.coverage"}:</td>
	<td class="formField"><input type="text" name="coverage" size="40" maxlength="255" value="{$coverage}" class="textField" /></td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="matchAll" value="1"{if $matchAll} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="search.matchAll"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="navigation.search"}" class="formButton" /></td>
</tr>
</table>

<br />
&#187 <a href="{$pageUrl}/search">{translate key="search.basicSearch"}</a>
</div>

<script type="text/javascript">document.search.query.focus();</script>
</form>

{include file="common/footer.tpl"}
