{**
 * articles.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of articles to potentially export
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.native.selectArticle"}
{assign var="pageCrumbTitle" value="plugins.importexport.native.selectArticle"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}

function toggleChecked() {
	var elements = document.articles.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name = 'articleId[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}
{/literal}
</script>

<br/>

<form action="{$pluginUrl}/exportArticles" method="post" name="articles">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="25%">{translate key="issue.issue"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=articles item=articleData}
	{assign var=article value=$articleData.article}
	{assign var=issue value=$articleData.issue}
	<tr valign="top">
		<td><input type="checkbox" name="issueId[]" value="{$article->getArticleId()}"/></td>
		<td><a href="{$pageUrl}/issue/issueToc/{$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()}</a></td>
		<td>{$article->getArticleTitle()|escape}</td>
		<td>{$article->getAuthorString()|escape}</td>
		<td align="right"><a href="{$pluginUrl}/exportArticle/{$article->getArticleId()}" class="action">{translate key="common.export"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $articles->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $articles->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$articles}</td>
		<td colspan="3" align="right">{page_links name="articles" iterator=$articles}</td>
	</tr>
{/if}
</table>
<p><input type="submit" value="{translate key="common.export"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onClick="toggleChecked()" /></p>
</form>

{include file="common/footer.tpl"}
