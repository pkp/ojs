{**
 * searchResults.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display article search results.
 *
 * $Id$
 *}
{assign var=pageTitle value="search.searchResults"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
<!--
function ensureKeyword() {
	if (document.search.query.value == '') {
		alert({/literal}'{translate|escape:"jsparam" key="search.noKeywordError"}'{literal});
		return false;
	}
	document.search.submit();
	return true;
}
// -->
{/literal}
</script>

<br/>

{if $basicQuery}
	<form method="post" name="search" action="{url op="results"}">
		<input type="text" size="40" maxlength="255" class="textField" name="query" value="{$basicQuery|escape}"/>&nbsp;&nbsp;
		<input type="hidden" name="searchField" value="{$searchField|escape}"/>
		<input type="submit" class="button defaultButton" onclick="ensureKeyword();" value="{translate key="common.search"}"/>
	</form>
	<br />
{else}
	<form name="revise" action="{url op="advanced"}" method="post">
		<input type="hidden" name="query" value="{$query|escape}"/>
		<input type="hidden" name="searchJournal" value="{$searchJournal|escape}"/>
		<input type="hidden" name="author" value="{$author|escape}"/>
		<input type="hidden" name="title" value="{$title|escape}"/>
		<input type="hidden" name="fullText" value="{$fullText|escape}"/>
		<input type="hidden" name="supplementaryFiles" value="{$supplementaryFiles|escape}"/>
		<input type="hidden" name="discipline" value="{$discipline|escape}"/>
		<input type="hidden" name="subject" value="{$subject|escape}"/>
		<input type="hidden" name="type" value="{$type|escape}"/>
		<input type="hidden" name="coverage" value="{$coverage|escape}"/>
		<input type="hidden" name="dateFromMonth" value="{$dateFromMonth|escape}"/>
		<input type="hidden" name="dateFromDay" value="{$dateFromDay|escape}"/>
		<input type="hidden" name="dateFromYear" value="{$dateFromYear|escape}"/>
		<input type="hidden" name="dateToMonth" value="{$dateToMonth|escape}"/>
		<input type="hidden" name="dateToDay" value="{$dateToDay|escape}"/>
		<input type="hidden" name="dateToYear" value="{$dateToYear|escape}"/>
	</form>
	<a href="javascript:document.revise.submit()" class="action">{translate key="search.reviseSearch"}</a><br />
{/if}

{call_hook name="Templates::Search::SearchResults::PreResults"}

{if $currentJournal}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<a name="results"></a>

<table width="100%" class="listing">
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentJournal}<td width="20%">{translate key="journal.journal"}</td>{/if}
	<td width="{if !$currentJournal}20%{else}40%{/if}">{translate key="issue.issue"}</td>
	<td width="60%" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
{assign var=section value=$result.section}
<tr valign="top">
	{if !$currentJournal}<td><a href="{url journal=$journal->getPath()}">{$journal->getJournalTitle()|escape}</a></td>{/if}
	<td><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId($journal)}">{$issue->getIssueIdentification()|escape}</a></td>
	<td width="30%">{$article->getArticleTitle()|strip_unsafe_html}</td>
	<td width="30%" align="right">
		{if $publishedArticle->getAccessStatus() || $issueAvailable}
			{assign var=hasAccess value=1}
		{else}
			{assign var=hasAccess value=0}
		{/if}
		{if $publishedArticle->getArticleAbstract() != ""}
			{assign var=hasAbstract value=1}
		{else}
			{assign var=hasAbstract value=0}
		{/if}
		{if !$hasAccess || $hasAbstract}<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId($journal)}" class="file">{if !$hasAbstract}{translate key="article.details"}{else}{translate key="article.abstract"}{/if}{/if}</a>{if $hasAccess}{foreach from=$publishedArticle->getLocalizedGalleys() item=galley name=galleyList}&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId($journal)|to_array:$galley->getBestGalleyId($journal)}" class="file">{$galley->getGalleyLabel()|escape}</a>{/foreach}{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols|escape}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$article->getAuthors() item=authorItem name=authorList}
			{$authorItem->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols|escape}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentJournal}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		{if $basicQuery}
			<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$basicQuery searchField=$searchField}</td>
		{else}
			<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$query searchJournal=$searchJournal author=$author title=$title fullText=$fullText supplementaryFiles=$supplementaryFiles discipline=$discipline subject=$subject type=$type coverage=$coverage dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear}</td>
		{/if}
	</tr>
{/if}
</table>

<p>{translate key="search.syntaxInstructions"}</p>

{include file="common/footer.tpl"}
