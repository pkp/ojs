{**
 * searchResults.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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

<br/>

{if $basicQuery}
	<form name="search" action="{$pageUrl}/search/results">
		<input type="text" size="40" maxlength="255" class="textField" name="query" value="{$basicQuery|escape}"/>&nbsp;&nbsp;
		<input type="hidden" name="searchField" value="{$searchField|escape}"/>
		<input type="submit" class="button defaultButton" onClick="ensureKeyword();" value="{translate key="common.search"}"/>
	</form>
	<br />
{else}
	<form name="revise" action="{$pageUrl}/search/advanced" method="post">
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
	<a href="javascript:document.revise.submit()" class="action">{translate key="search.reviseSearch"}</a><br />&nbsp;
{/if}

{if $currentJournal}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<table width="100%" class="listing">
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentJournal}<td width="20%">{translate key="journal.journal"}</td>{/if}
	<td width="{if !$currentJournal}20%{else}40%{/if}">{translate key="issue.issue"}</td>
	<td width="60%" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<tr valign="top">
	{if !$currentJournal}<td><a href="{$indexUrl}/{$journal->getPath()}">{$journal->getTitle()}</a></td>{/if}
	<td>{if $issue->getAccessStatus()}<a href="{$indexUrl}/{$journal->getPath()}/issue/view/{$issue->getBestIssueId($journal)}">{/if}{$issue->getIssueIdentification()}{if $issue->getAccessStatus()}</a>{/if}</td>
	<td width="30%">{$article->getArticleTitle()}</td>
	<td width="30%" align="right">
		<a href="{$indexUrl}/{$journal->getPath()}/article/view/{$publishedArticle->getBestArticleId($journal)}" class="file">{translate key="issue.abstract"}</a>{if ($issue->getAccessStatus() || $issueAvailable)}{foreach from=$publishedArticle->getGalleys() item=galley name=galleyList}&nbsp;<a href="{$indexUrl}/{$journal->getPath()}/article/view/{$publishedArticle->getBestArticleId($journal)}/{$galley->getGalleyId()}" class="file">{$galley->getLabel()}</a>{/foreach}{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$article->getAuthors() item=author name=authorList}
			{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentJournal}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">{page_links iterator=$results name="search"}</td>
	</tr>
{/if}
</table>

<p>{translate key="search.syntaxInstructions"}</p>

{include file="common/footer.tpl"}
