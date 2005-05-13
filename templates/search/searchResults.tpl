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

<br/>

<table width="100%" class="listing">
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="20%">{translate key="journal.journal"}</td>
	<td width="20%">{translate key="issue.issue"}</td>
	<td width="60%" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<tr valign="top">
	<td><a href="{$indexUrl}/{$journal->getPath()}">{$journal->getTitle()}</a></td>
	<td>{if $issue->getAccessStatus()}<a href="{$indexUrl}/{$journal->getPath()}/issue/view/{$issue->getBestIssueId($journal)}">{/if}{$issue->getIssueIdentification()}{if $issue->getAccessStatus()}</a>{/if}</td>
	<td width="35%">{$article->getArticleTitle()}</td>
	<td width="25%" align="right">
		<a href="{$indexUrl}/{$journal->getPath()}/article/view/{$publishedArticle->getBestArticleId($journal)}" class="file">{translate key="issue.abstract"}</a>
		{if ($issue->getAccessStatus() || $issueAvailable)}
		{foreach from=$publishedArticle->getGalleys() item=galley name=galleyList}
			&nbsp;
			<a href="{$indexUrl}/{$journal->getPath()}/article/view/{$publishedArticle->getBestArticleId($journal)}/{$galley->getGalleyId()}" class="file">{$galley->getLabel()}</a>
		{/foreach}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="4" style="padding-left: 30px;font-style: italic;">
		{foreach from=$article->getAuthors() item=author name=authorList}
			{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="4" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="4" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="4" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">{page_links iterator=$results name="search"}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
