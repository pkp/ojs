{**
 * plugins/importexport/erudit/index.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of article galleys to potentially export
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.erudit.selectArticle"}
{assign var="pageCrumbTitle" value="plugins.importexport.erudit.selectArticle"}
{include file="common/header.tpl"}
{/strip}

<br/>

<div id="articles">
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
		<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$article->getLocalizedTitle()|strip_unsafe_html}</td>
		<td>{$article->getAuthorString()|escape}</td>
		<td>
			{assign var="hasPriorAction" value=0}
			{foreach from=$publishedArticle->getGalleys() item=galley}
				{if $hasPriorAction}&nbsp;|&nbsp;{/if}
				<a href="{plugin_url path="exportGalley"|to_array:$article->getId():$galley->getId()}" class="action">{$galley->getGalleyLabel()|escape}</a>
				{assign var="hasPriorAction" value=1}
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
		<td colspan="3" align="right">{page_links anchor="articles" name="articles" iterator=$articles}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
