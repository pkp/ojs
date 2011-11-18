{**
 * @file plugins/importexport/medra/templates/articles.tpl
 *
 * Copyright (c) 2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select articles for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.medra.export.selectArticle"}
{assign var="pageCrumbTitle" value="plugins.importexport.medra.export.selectArticle"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.articles.elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'articleId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="articles">
	<p>{translate key="plugins.importexport.medra.workOrProduct"}</p>
	<form action="{plugin_url path="exportArticles"}" method="post" name="articles">
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

			{assign var="noArticles" value="true"}
			{iterate from=articles item=articleData}
				{assign var=article value=$articleData.publishedArticle}
				{if $article->getPubId('doi')}
					{assign var="noArticles" value="false"}
					{assign var=issue value=$articleData.issue}
					<tr valign="top">
						<td><input type="checkbox" name="articleId[]" value="{$article->getId()}"/></td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
						<td><a href="{url page="article" op="view" path=$article->getId()}" class="action">{$article->getLocalizedTitle()|strip_unsafe_html}</a></td>
						<td>{$article->getAuthorString()|escape}</td>
						<td align="right"><a href="{plugin_url path="exportArticle"|to_array:$article->getId()}" class="action">{translate key="common.export"}</a></td>
					</tr>
					<tr>
						<td colspan="5" class="{if $articles->eof()}end{/if}separator">&nbsp;</td>
					</tr>
				{/if}
			{/iterate}
			{if $noArticles == "true"}
				<tr>
					<td colspan="5" class="nodata">{translate key="plugins.importexport.medra.export.noArticles"}</td>
				</tr>
				<tr>
					<td colspan="5" class="endseparator">&nbsp;</td>
				</tr>
			{else}
				<tr>
					<td colspan="2" align="left">{page_info iterator=$articles}</td>
					<td colspan="3" align="right">{page_links anchor="articles" name="articles" iterator=$articles}</td>
				</tr>
			{/if}
		</table>
		<p>
			<input type="submit" name="export" value="{translate key="common.export"}" class="button defaultButton"/>
			&nbsp;
			<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
		</p>
	</form>
</div>

{include file="common/footer.tpl"}
