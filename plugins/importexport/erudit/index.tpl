{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of article galleys to potentially export
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.erudit.selectArticle"}
{assign var="pageCrumbTitle" value="plugins.importexport.erudit.selectArticle"}
{include file="common/header.tpl"}

<br/>

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="25%">{translate key="issue.issue"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="20%">{translate key="submission.galley"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=articles item=articleData}
	{assign var=article value=$articleData.article}
	{assign var=issue value=$articleData.issue}
	{assign var=publishedArticle value=$articleData.publishedArticle}
	<tr valign="top">
		<td><a href="{$pageUrl}/issue/issueToc/{$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|escape}</a></td>
		<td>{$article->getArticleTitle()|strip_unsafe_html}</td>
		<td>{$article->getAuthorString()|escape}</td>
		<td>
			{foreach from=$publishedArticle->getGalleys() item=galley}
				<a href="{$pluginUrl}/exportGalley/{$article->getArticleId()}/{$galley->getGalleyId()}" class="action">{$galley->getLabel()|escape}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $articles->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $articles->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$articles}</td>
		<td colspan="3" align="right">{page_links name="articles" iterator=$articles}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
