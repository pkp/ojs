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
<tr><td colspan="4" class="headseparator"></tr>
<tr class="heading" valign="bottom">
	<td width="20%">{translate key="journal.journal"}</td>
	<td width="20%">{translate key="issue.issue"}</td>
	<td width="60%" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="4" class="headseparator"></tr>

{foreach from=$results item=result name=results key=match}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<tr valign="top">
	<td><a href="{$indexUrl}/{$journal->getPath()}">{$journal->getTitle()}</a></td>
	<td>{if $issue->getAccessStatus()}<a href="{$indexUrl}/{$journal->getPath()}/issue/view/{$issue->getIssueId()}">{/if}{$issue->getIssueIdentification()}{if $issue->getAccessStatus()}</a>{/if}</td>
	<td width="35%">{$article->getArticleTitle()}</td>
	<td width="25%" align="right">
		<a href="{$pageUrl}/article/view/{$article->getArticleId()}" class="file">{translate key="issue.abstract"}</a>
		{if ($issue->getAccessStatus() || $issueAvailable)}
		{foreach from=$publishedArticle->getGalleys() item=galley name=galleyList}
			&nbsp;
			<a href="{$pageUrl}/article/{if not $galley->isHtmlGalley()}download/{$article->getArticleId()}/{$galley->getFileId()}{else}view/{$article->getArticleId()}/{$galley->getGalleyId()}{/if}" class="file">{$galley->getLabel()}</a>
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
<tr><td colspan="4" class="{if $smarty.foreach.results.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="4" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="4" class="endseparator"></tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
